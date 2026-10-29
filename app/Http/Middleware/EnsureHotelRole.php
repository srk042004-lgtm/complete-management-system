<?php

namespace App\Http\Middleware;

use App\Models\TrialAccount;
use App\Support\HotelFeatureCatalog;
use App\Support\HotelRoleCatalog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureHotelRole
{
    public function handle(Request $request, Closure $next, string $requiredRole = ''): Response
    {
        $user = $request->user();

        if ($user === null) {
            return redirect()->route('hotel-admin.login');
        }

        $userRole = HotelRoleCatalog::normalize((string) $user->role);
        $requiredRole = $requiredRole !== '' ? $requiredRole : (string) ($request->route('role') ?? $userRole);
        $requiredRole = in_array($requiredRole, ['owner', 'admin', 'manager', 'receptionist', 'housekeeping', 'accountant', 'corporate', 'maintenance'], true)
            ? HotelRoleCatalog::normalize($requiredRole)
            : $userRole;
        $userRole = in_array($userRole, ['owner', 'admin', 'manager', 'receptionist', 'housekeeping', 'accountant', 'corporate', 'maintenance'], true)
            ? $userRole
            : $this->homeRoleFor($userRole);

        if ($user->status !== 'active') {
            abort(Response::HTTP_FORBIDDEN, 'Active hotel staff access is required.');
        }

        if ($userRole !== $requiredRole) {
            $fallbackRole = $this->homeRoleFor($userRole);

            return redirect()->route('hotel.role.dashboard', ['role' => $fallbackRole]);
        }

        if ($user->tenant === null || $user->tenant->status !== 'active') {
            abort(Response::HTTP_FORBIDDEN, 'This hotel account is not currently active.');
        }

        $tier = HotelFeatureCatalog::operatingTier($user->tenant->hotel_type, $user->tenant->total_rooms);
        abort_unless(
            HotelRoleCatalog::allows($userRole, $user->tenant->moduleConfiguration(), $tier),
            Response::HTTP_FORBIDDEN,
            'This role is unavailable because its hotel module is disabled or restricted for this hotel tier.'
        );

        $subscription = $user->tenant->subscriptions()->latest('id')->first();
        if ($subscription?->status === 'trial') {
            $subscription = $subscription->expireIfNeeded();
        }
        if ($subscription?->status === 'expired') {
            TrialAccount::query()->where('subscription_id', $subscription->id)
                ->where('status', TrialAccount::STATUS_ACTIVE)
                ->update(['status' => TrialAccount::STATUS_EXPIRED]);
            abort(Response::HTTP_PAYMENT_REQUIRED, 'Your 10-day trial has ended. Please contact ArtFlow to activate a subscription.');
        }

        return $next($request);
    }

    protected function homeRoleFor(string $role): string
    {
        $role = in_array($role, ['owner', 'admin', 'manager', 'receptionist', 'housekeeping', 'accountant', 'corporate', 'maintenance'], true)
            ? $role
            : 'receptionist';

        return $role;
    }
}
