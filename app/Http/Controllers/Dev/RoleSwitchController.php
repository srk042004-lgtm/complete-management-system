<?php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use App\Support\HotelFeatureCatalog;
use App\Support\HotelRoleCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RoleSwitchController extends Controller
{
    private const ROLES = [
        'owner' => 'Hotel Owner',
        'admin' => 'Hotel Admin',
        'manager' => 'Hotel Manager',
        'receptionist' => 'Receptionist',
        'housekeeping' => 'Housekeeping',
    ];

    public function switchRole(Request $request, string $role): RedirectResponse
    {
        abort_unless(app()->environment(['local', 'testing']), 404);
        abort_unless(array_key_exists($role, self::ROLES), 404);

        $superAdmin = Auth::guard('super_admin')->user();
        abort_unless($superAdmin !== null && $superAdmin->is_active && $superAdmin->status === 'active', 403);

        $tenant = $this->resolveTenant($request, $role);
        $user = DB::transaction(function () use ($tenant, $role): User {
            $email = "role-switch+{$tenant->id}-{$role}@example.test";
            $existingUser = User::query()->where('email', $email)->first();

            if ($existingUser !== null) {
                abort_unless(
                    (int) $existingUser->tenant_id === (int) $tenant->id
                        && $existingUser->role === $role
                        && $existingUser->status === 'active',
                    409,
                    'The local role-switch account conflicts with an existing user and was not changed.'
                );

                return $existingUser;
            }

            return User::query()->create([
                'tenant_id' => $tenant->id,
                'name' => self::ROLES[$role] . ' (Local Test)',
                'email' => $email,
                'role' => $role,
                'status' => 'active',
                'password' => Str::random(48),
            ]);
        });

        Auth::guard('web')->login($user);
        Auth::shouldUse('web');
        $request->session()->regenerate();
        $request->session()->put('hotel_tenant_id', $tenant->id);
        $request->session()->put('impersonated_tenant_id', $tenant->id);
        $request->session()->put('impersonated_tenant_name', $tenant->hotel_name ?? $tenant->tenant_name ?? 'Tenant');
        $request->session()->forget('url.intended');

        Log::info('Local Super Admin role switch completed.', [
            'super_admin_id' => $superAdmin->id,
            'test_user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'role' => $role,
        ]);

        return redirect()
            ->route("{$role}.dashboard")
            ->with('success', 'Switched to the local ' . self::ROLES[$role] . ' test account.');
    }

    private function resolveTenant(Request $request, string $role): Tenant
    {
        // Prefer the hotel already selected in this session, but only if it can open its dashboards.
        $selectedTenantId = $request->session()->get('impersonated_tenant_id')
            ?? $request->session()->get('hotel_tenant_id');

        if ($selectedTenantId !== null) {
            $selected = Tenant::query()->whereKey($selectedTenantId)->where('status', 'active')->first();
            if ($selected !== null && $this->canOpenDashboards($selected) && $this->tenantSupportsRole($selected, $role)) {
                return $selected;
            }
        }

        // Otherwise pick the first hotel that is active, has a running trial or paid plan, and enables this role.
        $tenant = Tenant::query()
            ->where('status', 'active')
            ->orderBy('id')
            ->get()
            ->first(fn (Tenant $candidate): bool => $this->canOpenDashboards($candidate) && $this->tenantSupportsRole($candidate, $role));

        abort_if($tenant === null, 409, 'No active hotel with a running trial or plan has this role enabled for local testing.');

        return $tenant;
    }
    private function canOpenDashboards(Tenant $tenant): bool
    {
        $subscription = $tenant->subscriptions()->latest('id')->first();

        // Same rule as the dashboard middleware: blocked only once a trial or plan has actually expired.
        if ($subscription === null) {
            return true;
        }

        return $subscription->status === 'active'
            || ($subscription->status === 'trial' && ($subscription->trial_end === null || $subscription->trial_end->isFuture()));
    }

    private function tenantSupportsRole(Tenant $tenant, string $role): bool
    {
        $tier = HotelFeatureCatalog::operatingTier($tenant->hotel_type, $tenant->total_rooms);

        return HotelRoleCatalog::allows($role, $tenant->moduleConfiguration(), $tier);
    }
}
