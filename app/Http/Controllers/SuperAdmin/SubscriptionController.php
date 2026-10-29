<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\SaasPayment;
use App\Models\Subscription;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SubscriptionController extends Controller
{
    public function update(Request $request, int $tenantId): RedirectResponse
    {
        $user = Auth::guard('super_admin')->user();

        if ($user === null) {
            abort(403, 'Unauthorized.');
        }

        $validated = $request->validate([
            'plan_tier' => ['required', 'string', 'in:Monthly,Yearly,Lifetime'],
            'ends_at' => ['nullable', 'date'],
            'include_restaurant_module' => ['nullable', 'boolean'],
            'record_payment' => ['nullable', 'boolean'],
            'amount_received' => ['nullable', 'numeric', 'min:0'],
            'reference_number' => ['nullable', 'string', 'max:255'],
        ]);

        if ($request->boolean('record_payment')) {
            $request->validate([
                'amount_received' => ['required', 'numeric', 'min:0'],
                'reference_number' => ['required', 'string', 'max:255'],
            ]);
        }

        $tenant = Tenant::query()->findOrFail($tenantId);
        $subscription = Subscription::query()->where('tenant_id', $tenant->id)->latest('id')->first();

        if ($subscription === null) {
            abort(404, 'Subscription record not found.');
        }

        $planTier = strtolower($validated['plan_tier']);
        $endsAt = $planTier === 'lifetime' ? null : ($validated['ends_at'] ?? $this->defaultExpiryDate($planTier));

        DB::transaction(function () use ($request, $tenant, $subscription, $planTier, $endsAt, $validated, $user): void {
            $includeRestaurantModule = (bool) $request->boolean('include_restaurant_module');

            $subscription->plan_type = $planTier;
            $subscription->ends_at = $endsAt;
            $subscription->has_rooms = true;
            $subscription->has_restaurant_pos = $includeRestaurantModule;
            $subscription->restaurant_addon_price = $includeRestaurantModule ? (float) ($validated['amount_received'] ?? 0) : 0;
            $subscription->currency_code = $this->resolveCurrencyCode($tenant);

            if ($subscription->status === 'expired' || $subscription->status === 'suspended') {
                $subscription->status = 'active';
            }

            $subscription->save();

            $tenantModules = array_merge($tenant->moduleConfiguration(), [
                'rooms' => true,
                'restaurant' => $includeRestaurantModule,
            ]);
            if (array_key_exists('pos', $tenantModules)) {
                $tenantModules['pos'] = $includeRestaurantModule;
            }
            $tenant->enabled_modules = $tenantModules;
            $tenant->save();

            if ($request->boolean('record_payment')) {
                SaasPayment::query()->create([
                    'tenant_id' => $tenant->id,
                    'subscription_id' => $subscription->id,
                    'payment_reference' => $validated['reference_number'] ?? 'MANUAL-' . now()->timestamp,
                    'amount' => $validated['amount_received'] ?? 0,
                    'currency_code' => $this->resolveCurrencyCode($tenant),
                    'payment_status' => 'paid',
                    'gateway' => 'Manual Admin Adjustment',
                    'paid_at' => now(),
                ]);
            }

            Log::info('Admin ' . $user->name . ' updated subscription for ' . ($tenant->hotel_name ?? $tenant->tenant_name) . ' to ' . ucfirst($planTier) . ' plan.');
        });

        return redirect()->route('super-admin.dashboard')->with('success', 'Subscription updated successfully.');
    }

    private function resolveCurrencyCode(Tenant $tenant): string
    {
        return match ($tenant->country) {
            'Pakistan' => 'PKR',
            'United States' => 'USD',
            'United Kingdom' => 'GBP',
            'United Arab Emirates' => 'AED',
            default => 'USD',
        };
    }

    private function defaultExpiryDate(string $planTier): ?string
    {
        $offsetDays = match ($planTier) {
            'yearly' => 365,
            'monthly' => 30,
            default => 30,
        };

        return now()->addDays($offsetDays)->toDateString();
    }
}
