<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\IndustryRegistration;
use App\Models\Plan;
use App\Models\SaasPayment;
use App\Models\SuperAdminSetting;
use App\Support\HotelRoleCatalog;
use App\Support\SuperAdminActivity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class SubscriptionCheckoutController extends Controller
{
    private const PAYMENT_METHODS = ['easypaisa', 'jazzcash', 'bank_transfer', 'cash'];

    private const HOTEL_ROLES = [
        'hotel_admin',
        'owner',
        'admin',
        'receptionist',
        'housekeeping',
        'housekeeper',
        'accountant',
        'corporate',
        'maintenance',
    ];

    public function index(Request $request): View
    {
        $this->ensureHotelAccount($request);
        $tenant = $request->user()->tenant;
        $subscription = $tenant->subscriptions()->latest('id')->firstOrFail();
        if ($subscription->status === 'trial') {
            $subscription->expireIfNeeded();
        }
        $registrationDetails = $this->permanentRegistrationDetails((int) $tenant->id, $subscription->plan_type);
        $requestedCycle = $registrationDetails['billing_cycle'] ?? null;

        $plans = Plan::query()
            ->where('is_active', true)
            ->whereIn('billing_cycle', ['monthly', 'yearly', 'lifetime'])
            ->when($requestedCycle !== null, fn ($query) => $query->where('billing_cycle', $requestedCycle))
            ->when($registrationDetails['plan_id'] ?? null, fn ($query, $planId) => $query->whereKey($planId))
            ->orderBy('price')
            ->get();
        $latestPayment = SaasPayment::query()
            ->where('tenant_id', $tenant->id)
            ->whereNotNull('requested_plan_id')
            ->latest('id')
            ->first();
        $pendingPayment = $latestPayment?->payment_status === 'pending' ? $latestPayment : null;
        $setting = SuperAdminSetting::query()->where('key', 'subscription_payment_accounts')->first();
        $paymentAccounts = data_get($setting?->value, 'value', []);
        $support = SuperAdminSetting::query()->whereIn('key', ['support_email', 'support_phone'])->pluck('value', 'key');

        return view('account.subscription-checkout', compact(
            'tenant',
            'subscription',
            'requestedCycle',
            'plans',
            'pendingPayment',
            'latestPayment',
            'paymentAccounts',
            'support',
        ));
    }

    public function storePaymentRequest(Request $request): RedirectResponse
    {
        $this->ensureHotelAccount($request);
        $tenant = $request->user()->tenant;
        $subscription = $tenant->subscriptions()->latest('id')->firstOrFail();
        $registrationDetails = $this->permanentRegistrationDetails((int) $tenant->id, $subscription->plan_type);
        $requestedCycle = $registrationDetails['billing_cycle'] ?? null;
        $paymentSetting = SuperAdminSetting::query()->where('key', 'subscription_payment_accounts')->first();
        $paymentAccounts = data_get($paymentSetting?->value, 'value', []);
        $configuredMethods = array_keys(array_filter(
            is_array($paymentAccounts) ? $paymentAccounts : [],
            fn ($details): bool => is_array($details)
                && (filled($details['account_number'] ?? null)
                    || filled($details['iban'] ?? null)
                    || filled($details['instructions'] ?? null)),
        ));
        $planRule = Rule::exists('plans', 'id')
            ->where('is_active', true)
            ->whereIn('billing_cycle', ['monthly', 'yearly', 'lifetime']);
        if ($requestedCycle !== null) {
            $planRule->where('billing_cycle', $requestedCycle);
        }
        if ($registrationDetails['plan_id'] ?? null) {
            $planRule->where('id', $registrationDetails['plan_id']);
        }
        $validated = $request->validate([
            'plan_id' => ['required', 'integer', $planRule],
            'payment_method' => ['required', Rule::in(array_intersect(self::PAYMENT_METHODS, $configuredMethods))],
            'transaction_id' => ['required', 'string', 'max:120', Rule::unique('saas_payments', 'transaction_id')],
            'screenshot' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $proofPath = $request->file('screenshot')->store('subscription-payment-proofs', 'local');
        try {
            DB::transaction(function () use ($tenant, $subscription, $validated, $proofPath, $requestedCycle, $registrationDetails): void {
                $subscription = $tenant->subscriptions()
                    ->whereKey($subscription->id)
                    ->lockForUpdate()
                    ->firstOrFail();
                abort_if(
                    SaasPayment::query()->where('tenant_id', $tenant->id)
                        ->whereNotNull('requested_plan_id')->where('payment_status', 'pending')->exists(),
                    409,
                    'A subscription payment request is already awaiting review.'
                );
                $plan = Plan::query()
                    ->where('is_active', true)
                    ->whereIn('billing_cycle', ['monthly', 'yearly', 'lifetime'])
                    ->when($requestedCycle !== null, fn ($query) => $query->where('billing_cycle', $requestedCycle))
                    ->when($registrationDetails['plan_id'] ?? null, fn ($query, $planId) => $query->whereKey($planId))
                    ->lockForUpdate()
                    ->findOrFail($validated['plan_id']);
                $payment = SaasPayment::query()->create([
                    'tenant_id' => $tenant->id,
                    'subscription_id' => $subscription->id,
                    'requested_plan_id' => $plan->id,
                    'payment_reference' => 'SUB-'.strtoupper(Str::random(18)),
                    'amount' => $plan->price,
                    'currency_code' => $plan->currency_code,
                    'payment_status' => 'pending',
                    'gateway' => 'manual_subscription',
                    'payment_method' => $validated['payment_method'],
                    'transaction_id' => trim($validated['transaction_id']),
                    'notes' => 'Subscription payment request for '.$plan->name.' ('.$plan->billing_cycle.').',
                    'proof_path' => $proofPath,
                ]);
                IndustryRegistration::latestPermanentForTenant((int) $tenant->id)
                    ?->updatePaymentReviewStatus('awaiting_approval');
                SuperAdminActivity::notify(
                    'subscription.payment_submitted',
                    'Subscription payment needs review',
                    ($tenant->hotel_name ?? $tenant->tenant_name ?? $tenant->name).' submitted '.$payment->payment_reference.' for review.',
                    $payment,
                );
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($proofPath);
            throw $exception;
        }

        return redirect()->route('account.subscription')
            ->with('success', 'Your payment proof was submitted and is awaiting Super Admin review.');
    }

    public function paymentStatus(Request $request, string $paymentReference): JsonResponse
    {
        $this->ensureHotelAccount($request);
        $tenantId = (int) $request->user()->tenant_id;
        $payment = SaasPayment::query()
            ->where('tenant_id', $tenantId)
            ->whereNotNull('requested_plan_id')
            ->where('payment_reference', $paymentReference)
            ->firstOrFail();
        $subscription = $request->user()->tenant->subscriptions()
            ->whereKey($payment->subscription_id)
            ->firstOrFail();

        return response()->json([
            'payment_status' => $payment->payment_status,
            'subscription_status' => $subscription->status,
            'billing_cycle' => $payment->requestedPlan?->billing_cycle,
            'ends_at' => $subscription->ends_at?->toIso8601String(),
        ]);
    }

    private function ensureHotelAccount(Request $request): void
    {
        $user = $request->user();
        abort_unless($user !== null && $user->status === 'active', 403, 'An active hotel account is required to manage subscriptions.');
        abort_unless(
            $user->tenant_id !== null
                && $user->tenant?->status === 'active'
                && HotelRoleCatalog::normalize((string) $user->role) === 'owner',
            403,
            'Only an active hotel owner can manage subscriptions.'
        );
    }

    private function permanentRegistrationDetails(int $tenantId, string $fallbackCycle): ?array
    {
        $registration = new IndustryRegistration;
        $registration->setTable('hotel_registrations');
        $record = $registration->newQuery()
            ->where('tenant_id', $tenantId)
            ->latest('id')
            ->first();
        $details = $record?->details;

        if (! is_array($details) || ($details['account_type'] ?? null) !== 'permanent') {
            return null;
        }

        $cycle = $details['billing_cycle'] ?? $fallbackCycle;
        $details['billing_cycle'] = match ($cycle) {
            'annual' => 'yearly',
            'monthly', 'yearly', 'lifetime' => $cycle,
            default => $fallbackCycle,
        };

        return $details;
    }
}
