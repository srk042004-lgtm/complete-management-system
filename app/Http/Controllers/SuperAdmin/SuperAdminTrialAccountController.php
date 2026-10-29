<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\SaasPayment;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\TrialAccount;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SuperAdminTrialAccountController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:super_admin', 'super-admin']);
    }

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'system' => ['nullable', 'in:hotel,clinic,school,restaurant,retail'],
            'status' => ['nullable', 'in:pending_registration,active,expired,converted,cancelled'],
        ]);

        TrialAccount::expired()->get()->each(function (TrialAccount $trial): void {
            $trial->subscription?->expireIfNeeded();
            $trial->update(['status' => TrialAccount::STATUS_EXPIRED]);
        });

        $search = trim((string) ($filters['search'] ?? ''));
        $accounts = TrialAccount::query()
            ->with(['demoRequest:id,business_name,status', 'tenant:id,tenant_name,hotel_name,status', 'user:id,name,email', 'subscription:id,status,trial_end,plan_type,price,currency_code'])
            ->when($filters['system'] ?? null, fn (Builder $query, string $system) => $query->where('system', $system))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $nested) use ($search): void {
                    $nested->where('business_name', 'like', "%{$search}%")
                        ->orWhere('contact_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $counts = [
            'all' => TrialAccount::query()->count(),
            'pending' => TrialAccount::query()->where('status', TrialAccount::STATUS_PENDING)->count(),
            'active' => TrialAccount::query()->where('status', TrialAccount::STATUS_ACTIVE)->count(),
            'expired' => TrialAccount::query()->where('status', TrialAccount::STATUS_EXPIRED)->count(),
            'converted' => TrialAccount::query()->where('status', TrialAccount::STATUS_CONVERTED)->count(),
        ];

        return view('super-admin.trial-accounts', compact('accounts', 'counts', 'filters'));
    }

    public function convert(Request $request, TrialAccount $trialAccount): RedirectResponse
    {
        $validated = $request->validate([
            'plan_tier' => ['required', Rule::in(['monthly', 'yearly', 'lifetime'])],
            'amount_received' => ['nullable', 'numeric', 'min:0.01', 'max:99999999.99', 'required_if:plan_tier,monthly,yearly', 'required_with:payment_reference'],
            'payment_reference' => ['nullable', 'string', 'max:255', 'required_if:plan_tier,monthly,yearly', 'required_with:amount_received', Rule::unique('saas_payments', 'payment_reference')],
        ]);

        return DB::transaction(function () use ($validated, $trialAccount): RedirectResponse {
            $trial = TrialAccount::query()->lockForUpdate()->findOrFail($trialAccount->id);

            if ($trial->system !== 'hotel'
                || ! in_array($trial->status, [TrialAccount::STATUS_ACTIVE, TrialAccount::STATUS_EXPIRED], true)
                || $trial->tenant_id === null
                || $trial->subscription_id === null) {
                throw ValidationException::withMessages([
                    'conversion' => 'Only registered hotel trials can be converted.',
                ]);
            }

            $tenant = Tenant::query()->findOrFail($trial->tenant_id);
            $subscription = Subscription::query()
                ->whereKey($trial->subscription_id)
                ->where('tenant_id', $tenant->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! in_array($subscription->status, ['trial', 'expired'], true)) {
                throw ValidationException::withMessages([
                    'conversion' => 'This hotel no longer has a trial subscription to convert.',
                ]);
            }

            $planTier = $validated['plan_tier'];
            $amount = isset($validated['amount_received']) ? round((float) $validated['amount_received'], 2) : null;
            $now = now();
            $currency = $subscription->currency_code ?: ($tenant->currency_code ?: 'USD');
            $endsAt = match ($planTier) {
                'monthly' => $now->copy()->addDays(30),
                'yearly' => $now->copy()->addDays(365),
                default => null,
            };

            $subscription->forceFill([
                'plan_type' => $planTier,
                'price' => $amount ?? 0,
                'currency_code' => $currency,
                'status' => 'active',
                'starts_at' => $now,
                'trial_start' => null,
                'trial_end' => null,
                'trial_duration_days' => null,
                'ends_at' => $endsAt,
                'auto_renew' => false,
            ])->save();

            if ($tenant->status === 'trial') {
                $tenant->forceFill(['status' => 'active'])->save();
            }

            if ($amount !== null) {
                SaasPayment::query()->create([
                    'tenant_id' => $tenant->id,
                    'subscription_id' => $subscription->id,
                    'payment_reference' => $validated['payment_reference'],
                    'amount' => $amount,
                    'currency_code' => $currency,
                    'payment_status' => 'paid',
                    'gateway' => 'Manual Admin Confirmation',
                    'paid_at' => $now,
                ]);
            }

            $trial->forceFill(['status' => TrialAccount::STATUS_CONVERTED])->save();

            Log::info('Super admin converted a hotel trial account.', [
                'trial_account_id' => $trial->id,
                'tenant_id' => $tenant->id,
                'plan_tier' => $planTier,
                'payment_reference' => $validated['payment_reference'] ?? null,
                'super_admin_id' => Auth::guard('super_admin')->id(),
            ]);

            return redirect()->route('super-admin.trial-accounts')
                ->with('success', $trial->business_name . ' was converted to the ' . ucfirst($planTier) . ' plan.');
        });
    }
}
