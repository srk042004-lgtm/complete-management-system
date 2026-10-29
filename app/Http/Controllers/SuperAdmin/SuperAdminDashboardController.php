<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\SaasPayment;
use App\Models\Subscription;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

class SuperAdminDashboardController extends Controller
{
    private const DEFAULT_COUNTRY = 'Pakistan';

    private const COUNTRY_CURRENCY_MAP = [
        'Pakistan' => ['country' => 'Pakistan', 'code' => 'PKR', 'symbol' => 'Rs. ', 'rate' => 280.0],
        'United States' => ['country' => 'United States', 'code' => 'USD', 'symbol' => '$', 'rate' => 1.0],
        'United Kingdom' => ['country' => 'United Kingdom', 'code' => 'GBP', 'symbol' => '£', 'rate' => 0.78],
        'European Union' => ['country' => 'European Union', 'code' => 'EUR', 'symbol' => '€', 'rate' => 0.92],
        'United Arab Emirates' => ['country' => 'United Arab Emirates', 'code' => 'AED', 'symbol' => 'AED ', 'rate' => 3.67],
        'Saudi Arabia' => ['country' => 'Saudi Arabia', 'code' => 'SAR', 'symbol' => 'SAR ', 'rate' => 3.75],
        'India' => ['country' => 'India', 'code' => 'INR', 'symbol' => '₹', 'rate' => 83.2],
        'Turkey' => ['country' => 'Turkey', 'code' => 'TRY', 'symbol' => '₺', 'rate' => 32.8],
        'Bangladesh' => ['country' => 'Bangladesh', 'code' => 'BDT', 'symbol' => '৳', 'rate' => 117.6],
    ];

    private const COUNTRY_ALIASES = [
        'PK' => 'Pakistan',
        'PAK' => 'Pakistan',
        'USA' => 'United States',
        'US' => 'United States',
        'UK' => 'United Kingdom',
        'GB' => 'United Kingdom',
        'EU' => 'European Union',
        'UAE' => 'United Arab Emirates',
        'AE' => 'United Arab Emirates',
        'SA' => 'Saudi Arabia',
        'SAR' => 'Saudi Arabia',
        'IN' => 'India',
        'TR' => 'Turkey',
        'BD' => 'Bangladesh',
    ];

    public function __construct()
    {
        $this->middleware(['auth:super_admin', 'super-admin']);
    }

    public function index(Request $request): View
    {
        $this->authorizeSuperAdmin();

        try {
            $locationCategory = (string) $request->query('location_category', 'all');
            $countryFilter = $request->query('country');
            $selectedCountry = $this->normalizeCountry($countryFilter);
            $currencyConfig = $this->resolveCurrencyConfig($locationCategory, $selectedCountry);
            $now = Carbon::now();
            $windowStart = $now->copy()->subDays(30)->startOfDay();

            $tenantQuery = Tenant::query()->with([
                'subscriptions' => fn ($query) => $query->where('status', 'active')->latest('ends_at'),
                'payments' => fn ($query) => $query->where('payment_status', 'paid'),
            ]);

            $tenantQuery = $this->applyTenantFilters($tenantQuery, $locationCategory, $selectedCountry);
            $tenants = $tenantQuery->get();
            $tenantIds = $tenants->pluck('id')->all();

            $monthlyRevenue = (float) SaasPayment::query()
                ->whereIn('tenant_id', $tenantIds)
                ->where('payment_status', 'paid')
                ->whereNotNull('paid_at')
                ->where('paid_at', '>=', $windowStart)
                ->sum('amount');

            $lifetimeRevenue = (float) SaasPayment::query()
                ->whereIn('tenant_id', $tenantIds)
                ->where('payment_status', 'paid')
                ->sum('amount');

            $arr = $monthlyRevenue * 12;
            $activeTenants = $tenantQuery->clone()->where('status', 'active')->count();
            $suspendedTenants = $tenantQuery->clone()->where('status', 'suspended')->count();
            $pendingTenants = $tenantQuery->clone()->where('status', 'pending')->count();
            $totalUsers = \App\Models\User::query()->whereIn('tenant_id', $tenantIds)->count();
            $activeUsers = \App\Models\User::query()->whereIn('tenant_id', $tenantIds)->where('status', 'active')->count();
            $trialSubscriptions = Subscription::query()->whereIn('tenant_id', $tenantIds)->where('status', 'trial')->count();
            $expiredSubscriptions = Subscription::query()
                ->whereIn('tenant_id', $tenantIds)
                ->where(function (Builder $query) use ($now): void {
                    $query->where('status', 'expired')
                        ->orWhere(fn (Builder $nested) => $nested->whereNotNull('ends_at')->where('ends_at', '<', $now));
                })
                ->count();
            $pendingPayments = SaasPayment::query()
                ->whereIn('tenant_id', $tenantIds)
                ->where('payment_status', 'pending')
                ->count();

            $expiringSubscriptions = Subscription::query()
                ->with('tenant')
                ->whereIn('tenant_id', $tenantIds)
                ->where('status', 'active')
                ->whereNotNull('ends_at')
                ->whereBetween('ends_at', [$now, $now->copy()->addDays(7)])
                ->orderBy('ends_at', 'asc')
                ->paginate(8);

            $recentPayments = SaasPayment::query()
                ->with('tenant')
                ->whereIn('tenant_id', $tenantIds)
                ->latest('paid_at')
                ->limit(8)
                ->get();

            $countryOptions = Tenant::query()
                ->select('country')
                ->whereNotNull('country')
                ->distinct()
                ->pluck('country')
                ->sort()
                ->values()
                ->all();

            $tenantRows = $tenantQuery->paginate(10)->through(function (Tenant $tenant) use ($currencyConfig): array {
                $subscription = $tenant->subscriptions->first();
                $tenantRevenue = $tenant->payments
                    ->where('payment_status', 'paid')
                    ->sum('amount');

                return [
                    'tenant' => $tenant,
                    'subscription' => $subscription,
                    'revenue_contributed' => $this->convertCurrencyValue($tenantRevenue, $currencyConfig['rate']),
                    'days_left' => $this->getDaysRemaining($subscription),
                ];
            });

            return view('super-admin.dashboard', [
                'recentActivity' => \App\Models\ActivityLog::query()->latest('id')->limit(8)->get(),
                'pendingPayments' => SaasPayment::query()->with('tenant:id,tenant_name,hotel_name')->where('payment_status', 'pending')->latest('id')->limit(6)->get(),
                'metrics' => [
                    'mrr' => $this->convertCurrencyValue($monthlyRevenue, $currencyConfig['rate']),
                    'arr' => $this->convertCurrencyValue($arr, $currencyConfig['rate']),
                    'lifetime_revenue' => $this->convertCurrencyValue($lifetimeRevenue, $currencyConfig['rate']),
                    'tenant_counts' => [
                        'active' => $activeTenants,
                        'suspended' => $suspendedTenants,
                        'pending' => $pendingTenants,
                    ],
                    'user_counts' => [
                        'total' => $totalUsers,
                        'active' => $activeUsers,
                    ],
                    'subscription_counts' => [
                        'trial' => $trialSubscriptions,
                        'expired' => $expiredSubscriptions,
                    ],
                    'pending_payments' => $pendingPayments,
                    'currency' => $currencyConfig,
                    'country_filter' => $selectedCountry,
                    'location_category' => $locationCategory,
                    'country_options' => $countryOptions,
                    'expiring_subscriptions' => $expiringSubscriptions,
                    'recent_payments' => $recentPayments->map(function (SaasPayment $payment) use ($currencyConfig): array {
                        return [
                            'payment_reference' => $payment->payment_reference,
                            'tenant_name' => $payment->tenant?->tenant_name,
                            'amount' => $this->convertCurrencyValue((float) $payment->amount, $currencyConfig['rate']),
                            'currency_code' => $currencyConfig['code'],
                            'payment_status' => $payment->payment_status,
                            'paid_at' => $payment->paid_at?->toDateTimeString(),
                        ];
                    })->values()->all(),
                ],
                'tenants' => $tenantRows,
                'country_filter' => $selectedCountry,
                'location_category' => $locationCategory,
                'country_options' => $countryOptions,
                'currency' => $currencyConfig,
            ]);
        } catch (Throwable $exception) {
            Log::error('Super admin dashboard metrics aggregation failed.', [
                'exception' => $exception,
            ]);

            throw $exception;
        }
    }

    public function impersonateAccess(Tenant $tenant): RedirectResponse
    {
        $this->authorizeSuperAdmin();

        try {
            session()->put('impersonated_tenant_id', $tenant->id);
            session()->put('impersonated_tenant_name', $tenant->tenant_name);

            return redirect()->route('frontdesk.dashboard')
                ->with('success', sprintf('You are now impersonating %s.', $tenant->tenant_name));
        } catch (Throwable $exception) {
            Log::error('Failed to impersonate tenant from super admin dashboard.', [
                'tenant_id' => $tenant->id,
                'exception' => $exception,
            ]);

            throw $exception;
        }
    }

    public function extendAccess(Request $request, Tenant $tenant): RedirectResponse
    {
        $this->authorizeSuperAdmin();

        try {
            $subscription = $tenant->subscriptions()
                ->where('status', 'active')
                ->latest('ends_at')
                ->first();

            if ($subscription !== null) {
                $subscription->ends_at = Carbon::now()->addDays(30);
                $subscription->save();
                $tenant->status = 'active';
                $tenant->save();
            }

            return redirect()->back()->with('success', 'Tenant access extended successfully.');
        } catch (Throwable $exception) {
            Log::error('Failed to extend tenant access from super admin dashboard.', [
                'tenant_id' => $tenant->id,
                'exception' => $exception,
            ]);

            throw $exception;
        }
    }

    public function suspendAccess(Tenant $tenant): RedirectResponse
    {
        $this->authorizeSuperAdmin();

        try {
            $tenant->status = 'suspended';
            $tenant->save();

            return redirect()->back()->with('success', 'Tenant access suspended successfully.');
        } catch (Throwable $exception) {
            Log::error('Failed to suspend tenant access from super admin dashboard.', [
                'tenant_id' => $tenant->id,
                'exception' => $exception,
            ]);

            throw $exception;
        }
    }

    private function authorizeSuperAdmin(): void
    {
        $user = Auth::guard('super_admin')->user();

        if ($user === null || $user->is_active !== true) {
            abort(403, 'Access denied. Super Admin role required.');
        }
    }

    private function applyTenantFilters(Builder $query, string $locationCategory, string $countryFilter): Builder
    {
        $query->when($locationCategory === 'domestic', function (Builder $builder): void {
            $builder->where('country', '=', self::DEFAULT_COUNTRY);
        });

        $query->when($locationCategory === 'international', function (Builder $builder): void {
            $builder->where('country', '!=', self::DEFAULT_COUNTRY);
        });

        $query->when($countryFilter !== '', function (Builder $builder) use ($countryFilter): void {
            $builder->where('country', '=', $countryFilter);
        });

        return $query;
    }

    private function normalizeCountry(?string $country): string
    {
        $country = trim((string) $country);

        if ($country === '') {
            return self::DEFAULT_COUNTRY;
        }

        return self::COUNTRY_ALIASES[strtoupper($country)] ?? $country;
    }

    private function resolveCurrencyConfig(string $locationCategory, string $countryFilter): array
    {
        $country = self::DEFAULT_COUNTRY;

        if ($locationCategory === 'international' && $countryFilter !== '') {
            $country = $countryFilter;
        }

        if ($locationCategory === 'all' && $countryFilter !== '') {
            $country = $countryFilter;
        }

        return self::COUNTRY_CURRENCY_MAP[$country] ?? self::COUNTRY_CURRENCY_MAP[self::DEFAULT_COUNTRY];
    }

    private function convertCurrencyValue(float $amount, float $rate): float
    {
        return round($amount * $rate, 2);
    }

    private function getDaysRemaining(?Subscription $subscription): string|int
    {
        if ($subscription === null) {
            return 'n/a';
        }

        if ($subscription->plan_type === 'lifetime') {
            return 'Lifetime';
        }

        if ($subscription->ends_at === null) {
            return 'n/a';
        }

        return max(0, (int) floor(Carbon::now()->diffInDays($subscription->ends_at, false)));
    }
}
