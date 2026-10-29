<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\SaasPayment;
use App\Models\Tenant;
use App\Support\SuperAdminActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Models\Plan;
use App\Models\SaasLedger;
use App\Models\Subscription;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SuperAdminPaymentsController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'in:pending,paid,failed,refunded,cancelled'],
            'tenant_id' => ['nullable', 'integer', 'exists:tenants,id'],
        ]);

        $payments = SaasPayment::query()
            ->with(['tenant:id,hotel_name,tenant_name', 'subscription:id,plan_id,plan_type'])
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $nested) use ($search): void {
                    $nested->where('payment_reference', 'like', "%{$search}%")
                        ->orWhere('transaction_id', 'like', "%{$search}%")
                        ->orWhereHas('tenant', fn (Builder $tenant) => $tenant->where('hotel_name', 'like', "%{$search}%"));
                });
            })
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('payment_status', $status))
            ->when($filters['tenant_id'] ?? null, fn (Builder $query, int $tenantId) => $query->where('tenant_id', $tenantId))
            ->latest('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('super-admin.payments', [
            'payments' => $payments,
            'tenants' => Tenant::query()->orderBy('hotel_name')->get(['id', 'hotel_name', 'tenant_name']),
            'filters' => $filters,
        ]);
    }

    public function markPaid(SaasPayment $payment): RedirectResponse
    {
        DB::transaction(function () use ($payment): void {
            $payment = SaasPayment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();
            abort_unless($payment->payment_status === 'pending', 409, 'Only pending payments can be approved.');

            $payment->update([
                'payment_status' => 'paid',
                'paid_at' => now(),
            ]);

            $this->activateSubscription($payment);
            \App\Models\IndustryRegistration::latestPermanentForTenant((int) $payment->tenant_id)?->updatePaymentReviewStatus('approved');

            SaasLedger::query()->create([
                'tenant_id' => $payment->tenant_id,
                'invoice_number' => $payment->payment_reference,
                'transaction_type' => 'subscription_base',
                'payment_method' => $payment->payment_method ?? 'manual',
                'gross_amount' => $payment->amount,
                'tax_amount' => 0,
                'net_amount' => $payment->amount,
                'currency_code' => $payment->currency_code,
                'payment_status' => 'successful',
            ]);

            SuperAdminActivity::log('payment.approved', $payment, "Payment {$payment->payment_reference} was marked paid.");
        });

        return back()->with('success', 'Payment marked as paid.');
    }

    public function reject(Request $request, SaasPayment $payment): RedirectResponse
    {
        $validated = $request->validate(['review_notes' => ['required', 'string', 'max:500']]);

        DB::transaction(function () use ($payment, $validated): void {
            $payment = SaasPayment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();
            abort_unless($payment->payment_status === 'pending', 409, 'Only pending payments can be rejected.');

            \App\Models\IndustryRegistration::latestPermanentForTenant((int) $payment->tenant_id)?->updatePaymentReviewStatus('rejected');
            $payment->update([
                'payment_status' => 'failed',
                'notes' => trim(($payment->notes ? $payment->notes."\n" : '').'Rejected: '.$validated['review_notes']),
            ]);

            SuperAdminActivity::log('payment.rejected', $payment, "Payment {$payment->payment_reference} was rejected.");
        });

        return back()->with('success', 'Payment rejected.');
    }

    public function refund(SaasPayment $payment): RedirectResponse
    {
        DB::transaction(function () use ($payment): void {
            $payment->update([
                'payment_status' => 'refunded',
                'refunded_at' => now(),
            ]);

            SaasLedger::query()
                ->where('invoice_number', $payment->payment_reference)
                ->update(['payment_status' => 'refunded']);

            SuperAdminActivity::log('payment.refunded', $payment, "Payment {$payment->payment_reference} was refunded.");
        });

        return back()->with('success', 'Payment marked as refunded.');
    }

    /** Streams the hotel's uploaded payment proof to the Super Admin reviewer only. */
    public function proof(SaasPayment $payment): BinaryFileResponse
    {
        abort_if($payment->proof_path === null || ! Storage::disk('local')->exists($payment->proof_path), 404);

        return response()->file(Storage::disk('local')->path($payment->proof_path));
    }

    private function activateSubscription(SaasPayment $payment): void
    {
        $plan = $payment->requested_plan_id ? Plan::query()->find($payment->requested_plan_id) : null;
        $subscription = Subscription::query()->whereKey($payment->subscription_id)->lockForUpdate()->first();
        if ($plan === null || $subscription === null) {
            return;
        }

        $startsAt = now();
        $endsAt = match ($plan->billing_cycle) {
            'monthly' => $startsAt->copy()->addMonthNoOverflow(),
            'yearly' => $startsAt->copy()->addYearNoOverflow(),
            default => null,
        };

        $subscription->forceFill([
            'plan_id' => $plan->id,
            'plan_type' => $plan->billing_cycle,
            'price' => $plan->price,
            'currency_code' => $plan->currency_code,
            'status' => 'active',
            'starts_at' => $startsAt,
            'trial_start' => null,
            'trial_end' => null,
            'ends_at' => $endsAt,
            'expires_at' => $endsAt,
        ])->save();
    }
}
