<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\Plan;
use App\Support\SuperAdminActivity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class PlanController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:super_admin', 'super-admin']);
    }

    public function index(): View
    {
        $plans = Plan::query()
            ->latest('id')
            ->paginate(10);

        return view('super-admin.plan-manager', [
            'plans' => $plans,
        ]);
    }

    public function storePlan(Request $request): RedirectResponse
    {
        $plan = Plan::query()->create($this->validatedPlan($request));
        SuperAdminActivity::log('plan.created', $plan, "Plan {$plan->name} was created.");

        return back()->with('success', 'Subscription plan created.');
    }

    public function updatePlan(Request $request, Plan $plan): RedirectResponse
    {
        $plan->update($this->validatedPlan($request));
        SuperAdminActivity::log('plan.updated', $plan, "Plan {$plan->name} was updated.");

        return back()->with('success', 'Subscription plan updated.');
    }

    public function togglePlan(Plan $plan): RedirectResponse
    {
        $plan->update(['is_active' => ! $plan->is_active]);
        SuperAdminActivity::log('plan.status_changed', $plan, "Plan {$plan->name} was " . ($plan->is_active ? 'activated.' : 'deactivated.'));

        return back()->with('success', 'Subscription plan status updated.');
    }

    public function update(Request $request, int $subscriptionId): RedirectResponse
    {
        $user = Auth::guard('super_admin')->user();

        if ($user === null) {
            abort(403, 'Unauthorized.');
        }

        $validated = $request->validate([
            'plan_type' => ['required', 'string', 'in:monthly,yearly,lifetime'],
            'price' => ['required', 'numeric', 'min:0'],
            'currency_code' => ['required', 'string', 'max:3'],
            'has_rooms' => ['required', 'boolean'],
            'has_restaurant_pos' => ['required', 'boolean'],
            'restaurant_addon_price' => ['nullable', 'numeric', 'min:0'],
        ]);

        $subscription = Subscription::query()->with('tenant')->findOrFail($subscriptionId);
        $hasRooms = (bool) $validated['has_rooms'];
        $hasRestaurantPos = (bool) $validated['has_restaurant_pos'];
        $restaurantAddonPrice = (float) ($validated['restaurant_addon_price'] ?? 0);

        DB::transaction(function () use ($subscription, $validated, $hasRooms, $hasRestaurantPos, $restaurantAddonPrice): void {
            $subscription->update([
                'plan_type' => $validated['plan_type'],
                'price' => (float) $validated['price'],
                'currency_code' => strtoupper($validated['currency_code']),
                'has_rooms' => $hasRooms,
                'has_restaurant_pos' => $hasRestaurantPos,
                'restaurant_addon_price' => $restaurantAddonPrice,
            ]);

            $subscriptionsToSync = Subscription::query()
                ->where('plan_type', $validated['plan_type'])
                ->where('status', 'active')
                ->get();

            foreach ($subscriptionsToSync as $item) {
                $item->update([
                    'has_rooms' => $hasRooms,
                    'has_restaurant_pos' => $hasRestaurantPos,
                    'restaurant_addon_price' => $restaurantAddonPrice,
                    'currency_code' => strtoupper($validated['currency_code']),
                    'price' => (float) $validated['price'],
                ]);

                if ($item->tenant !== null) {
                    $item->tenant->enabled_modules = [
                        'rooms' => $hasRooms,
                        'restaurant' => $hasRestaurantPos,
                    ];

                    $item->tenant->save();
                }
            }
        });

        Log::info('Super admin plan configuration updated.', [
            'super_admin_id' => $user->id,
            'subscription_id' => $subscription->id,
            'plan_type' => $validated['plan_type'],
            'has_rooms' => $validated['has_rooms'],
            'has_restaurant_pos' => $validated['has_restaurant_pos'],
        ]);

        return redirect()->route('super-admin.subscriptions')->with('success', 'Subscription plan features updated successfully.');
    }

    private function validatedPlan(Request $request): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:2000'],
            'billing_cycle' => ['required', 'in:monthly,yearly,lifetime'],
            'price' => ['required', 'numeric', 'min:0'],
            'currency_code' => ['required', 'string', 'size:3'],
            'trial_days' => ['required', 'integer', 'min:0', 'max:365'],
            'max_rooms' => ['nullable', 'integer', 'min:1'],
            'max_users' => ['nullable', 'integer', 'min:1'],
            'max_staff' => ['nullable', 'integer', 'min:1'],
            'max_reservations' => ['nullable', 'integer', 'min:1'],
            'has_rooms' => ['nullable', 'boolean'],
            'has_restaurant_pos' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        return array_merge($validated, [
            'currency_code' => strtoupper($validated['currency_code']),
            'has_rooms' => $request->boolean('has_rooms'),
            'has_restaurant_pos' => $request->boolean('has_restaurant_pos'),
            'is_active' => $request->boolean('is_active', true),
        ]);
    }
}
