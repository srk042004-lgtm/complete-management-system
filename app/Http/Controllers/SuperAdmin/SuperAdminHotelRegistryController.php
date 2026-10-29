<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Support\SuperAdminActivity;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;

class SuperAdminHotelRegistryController extends Controller
{
    private const COUNTRY_CURRENCY_MAP = [
        'Pakistan' => ['symbol' => 'Rs.', 'code' => 'PKR', 'format' => 'Rs. 1,234.56'],
        'United States' => ['symbol' => '$', 'code' => 'USD', 'format' => '$1,234.56'],
        'United Kingdom' => ['symbol' => '£', 'code' => 'GBP', 'format' => '£1,234.56'],
        'United Arab Emirates' => ['symbol' => 'AED', 'code' => 'AED', 'format' => 'AED 1,234.56'],
    ];

    public function create(): View
    {
        return view('super-admin.hotel-registry-create', [
            'countries' => $this->countryOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'hotel_name' => ['required', 'string', 'max:255'],
            'property_type' => ['required', 'string', 'max:255'],
            'owner_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:tenants,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'country' => ['required', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'website' => ['nullable', 'url', 'max:255'],
            'status' => ['nullable', 'string', 'in:pending,active,suspended'],
            'notes' => ['nullable', 'string'],
            'include_restaurant_module' => ['nullable', 'boolean'],
        ]);

        Tenant::query()->create([
            'tenant_name' => $validated['hotel_name'],
            'owner_email' => $validated['email'],
            'hotel_name' => $validated['hotel_name'],
            'property_type' => $validated['property_type'],
            'owner_name' => $validated['owner_name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'country' => $validated['country'],
            'city' => $validated['city'] ?? null,
            'address' => $validated['address'] ?? null,
            'website' => $validated['website'] ?? null,
            'status' => $validated['status'] ?? 'pending',
            'notes' => $validated['notes'] ?? null,
            'enabled_modules' => [
                'rooms' => true,
                'restaurant' => (bool) ($validated['include_restaurant_module'] ?? false),
            ],
        ]);

        return redirect()->route('super-admin.hotel-registry')->with('success', 'Hotel added manually to the registry.');
    }

    public function index(Request $request): View
    {
        $region = (string) $request->query('region', 'all');
        $country = (string) $request->query('country', '');
        $subscriptionStatus = (string) $request->query('subscription_status', 'all');
        $search = trim((string) $request->query('search', ''));

        $query = Tenant::query()
            ->with(['subscriptions' => fn ($query) => $query->latest('ends_at'), 'payments'])
            ->when($region === 'domestic', fn (Builder $builder): Builder => $builder->where('country', '=', 'Pakistan'))
            ->when($region === 'international', fn (Builder $builder): Builder => $builder->where('country', '!=', 'Pakistan'))
            ->when($country !== '', fn (Builder $builder) => $builder->where('country', '=', $country))
            ->when($subscriptionStatus !== 'all', fn (Builder $builder) => $builder->where('status', '=', $subscriptionStatus))
            ->when($search !== '', function (Builder $builder) use ($search): void {
                $builder->where(function (Builder $where) use ($search): void {
                    $where->where('hotel_name', 'like', '%' . $search . '%')
                        ->orWhere('owner_name', 'like', '%' . $search . '%')
                        ->orWhere('email', 'like', '%' . $search . '%');
                });
            });

        $tenants = $query->latest('created_at')->paginate(10)->appends($request->query());
        $countryOptions = Tenant::query()->select('country')->whereNotNull('country')->distinct()->pluck('country')->sort()->values()->all();

        return view('super-admin.hotel-registry', [
            'tenants' => $tenants,
            'countryOptions' => $countryOptions,
            'region' => $region,
            'country' => $country,
            'subscriptionStatus' => $subscriptionStatus,
            'search' => $search,
            'currencyMap' => self::COUNTRY_CURRENCY_MAP,
        ]);
    }

    public function impersonate(Tenant $tenant): RedirectResponse
    {
        $user = Auth::guard('super_admin')->user();

        if ($user === null) {
            abort(403);
        }

        try {
            session()->put('impersonated_tenant_id', $tenant->id);
            session()->put('impersonated_tenant_name', $tenant->hotel_name ?? $tenant->tenant_name ?? 'Tenant');

            Log::info('Super admin impersonated tenant dashboard.', [
                'super_admin_id' => $user->id,
                'tenant_id' => $tenant->id,
            ]);

            return redirect()->route('frontdesk.dashboard')->with('success', 'You are now impersonating the tenant dashboard.');
        } catch (\Throwable $exception) {
            Log::error('Failed to impersonate tenant dashboard.', [
                'tenant_id' => $tenant->id,
                'exception' => $exception,
            ]);

            throw $exception;
        }
    }

    public function toggleStatus(Tenant $tenant): RedirectResponse
    {
        return $tenant->status === 'active'
            ? $this->suspend($tenant)
            : $this->activate($tenant);
    }

    public function approve(Tenant $tenant): RedirectResponse
    {
        DB::transaction(function () use ($tenant): void {
            $tenant->update([
                'status' => 'active',
                'approved_at' => now(),
                'rejection_reason' => null,
            ]);
            SuperAdminActivity::log('hotel.approved', $tenant, "Hotel {$tenant->hotel_name} was approved.");
            SuperAdminActivity::notify('hotel.approved', 'Hotel approved', "{$tenant->hotel_name} is now active.", $tenant);
        });

        return back()->with('success', 'Hotel approved and activated.');
    }

    public function reject(Request $request, Tenant $tenant): RedirectResponse
    {
        $validated = $request->validate(['rejection_reason' => ['required', 'string', 'max:1000']]);

        DB::transaction(function () use ($tenant, $validated): void {
            $tenant->update([
                'status' => 'suspended',
                'rejection_reason' => $validated['rejection_reason'],
            ]);
            SuperAdminActivity::log('hotel.rejected', $tenant, "Hotel {$tenant->hotel_name} was rejected: {$validated['rejection_reason']}");
        });

        return back()->with('success', 'Hotel rejected.');
    }

    public function suspend(Tenant $tenant): RedirectResponse
    {
        DB::transaction(function () use ($tenant): void {
            $tenant->update(['status' => 'suspended']);
            SuperAdminActivity::log('hotel.suspended', $tenant, "Hotel {$tenant->hotel_name} was suspended.");
        });

        return back()->with('success', 'Hotel suspended.');
    }

    public function activate(Tenant $tenant): RedirectResponse
    {
        DB::transaction(function () use ($tenant): void {
            $tenant->update(['status' => 'active', 'rejection_reason' => null]);
            SuperAdminActivity::log('hotel.activated', $tenant, "Hotel {$tenant->hotel_name} was activated.");
        });

        return back()->with('success', 'Hotel activated.');
    }

    private function countryOptions(): array
    {
        return [
            'Pakistan',
            'United States',
            'United Kingdom',
            'United Arab Emirates',
            'Saudi Arabia',
            'India',
            'Turkey',
            'Bangladesh',
        ];
    }

    public function manageSubscription(Request $request, Tenant $tenant): RedirectResponse
    {
        $request->validate([
            'plan_type' => ['required', 'string', 'in:monthly,yearly,lifetime'],
            'days' => ['nullable', 'integer', 'min:1'],
        ]);

        $subscription = $tenant->subscriptions()->latest('ends_at')->first();

        if ($subscription === null) {
            $subscription = $tenant->subscriptions()->create([
                'plan_type' => $request->input('plan_type'),
                'status' => 'active',
                'auto_renew' => true,
                'starts_at' => now(),
                'ends_at' => now()->addDays((int) $request->input('days', 30)),
                'price' => 0,
            ]);
        } else {
            $subscription->plan_type = $request->input('plan_type');
            $subscription->status = 'active';
            $subscription->ends_at = $subscription->ends_at?->addDays((int) $request->input('days', 30)) ?? now()->addDays((int) $request->input('days', 30));
            $subscription->save();
        }

        return redirect()->back()->with('success', 'Subscription updated successfully.');
    }
}
