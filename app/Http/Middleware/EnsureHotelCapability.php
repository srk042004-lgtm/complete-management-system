<?php

namespace App\Http\Middleware;

use App\Models\TrialAccount;
use App\Support\HotelFeatureCatalog;
use App\Support\HotelRoleCatalog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureHotelCapability
{
    private const CAPABILITY_ROLES = [
        'dashboard.read' => ['hotel_admin', 'admin', 'owner', 'manager', 'receptionist', 'housekeeping', 'housekeeper', 'accountant', 'corporate', 'maintenance'],
        'frontdesk.dashboard' => ['hotel_admin', 'admin', 'manager', 'receptionist'],
        'reservations.read' => ['hotel_admin', 'admin', 'owner', 'manager', 'receptionist'],
        'reservations.write' => ['hotel_admin', 'admin', 'manager', 'receptionist'],
        'folio.manage' => ['hotel_admin', 'admin', 'manager', 'receptionist'],
        'rooms.read' => ['hotel_admin', 'admin', 'owner'],
        'rooms.manage' => ['hotel_admin', 'admin', 'receptionist'],
        'room.status' => ['hotel_admin', 'admin', 'manager', 'receptionist', 'housekeeping', 'housekeeper'],
        'staff.manage' => ['hotel_admin', 'admin', 'owner'],
        'reports.read' => ['hotel_admin', 'owner', 'manager', 'accountant', 'corporate'],
        'finance.read' => ['hotel_admin', 'owner', 'accountant', 'corporate'],
        'finance.write' => ['hotel_admin', 'admin', 'accountant', 'receptionist'],
        'pos.access' => ['hotel_admin', 'admin', 'receptionist'],
        'housekeeping.access' => ['hotel_admin', 'admin', 'manager', 'housekeeping', 'housekeeper'],
        'settings.manage' => ['hotel_admin', 'admin'],
        'subscription.manage' => ['owner'],
        'audit.read' => ['owner'],
        'policy.manage' => ['owner', 'hotel_admin', 'admin'],
        'inventory.manage' => ['hotel_admin', 'admin', 'receptionist'],
        'inventory.read' => ['hotel_admin', 'admin', 'receptionist'],
        'night-audit.run' => ['admin'],
    ];

    public function handle(Request $request, Closure $next, string $capability): mixed
    {
        $user = $request->user();
        if ($user === null) {
            return redirect()->route('hotel-admin.login');
        }

        abort_unless($user->status === 'active' && $user->tenant?->status === 'active', Response::HTTP_FORBIDDEN);

        $role = HotelRoleCatalog::normalize((string) $user->role);
        $allowedRoles = array_map(static fn (string $allowedRole): string => HotelRoleCatalog::normalize($allowedRole), self::CAPABILITY_ROLES[$capability] ?? []);
        abort_unless(in_array($role, $allowedRoles, true), Response::HTTP_FORBIDDEN, 'Your hotel role is not allowed to perform this action.');

        abort_unless(
            in_array($capability, HotelRoleCatalog::allowedCapabilities($role), true),
            Response::HTTP_FORBIDDEN,
            'Your hotel role is not authorized for this operation.'
        );

        $tier = HotelFeatureCatalog::operatingTier($user->tenant->hotel_type, $user->tenant->total_rooms);
        abort_unless(
            HotelRoleCatalog::allows($role, $user->tenant->moduleConfiguration(), $tier),
            Response::HTTP_FORBIDDEN,
            'This role is unavailable because its hotel module is disabled or restricted for this hotel tier.'
        );

        $feature = match ($capability) {
            'reservations.read', 'reservations.write' => 'reservations',
            'rooms.read', 'rooms.manage' => 'rooms',
            'staff.manage' => 'staff',
            'reports.read' => 'reports',
            'finance.read', 'finance.write' => 'finance',
            'pos.access' => 'pos',
            'housekeeping.access' => 'housekeeping',
            'room.status' => 'housekeeping',
            'night-audit.run' => 'finance',
            default => null,
        };
        if ($feature !== null) {
            abort_unless($user->tenant->hasFeature($feature), Response::HTTP_FORBIDDEN, 'This module is disabled for your hotel. Ask your hotel administrator to enable it in Hotel Features.');
        }

        if ($capability === 'pos.access' && ! $user->tenant->hasFeature('restaurant')) {
            abort(Response::HTTP_FORBIDDEN, 'Restaurant POS is not available because the restaurant module is disabled for this hotel.');
        }

        $subscription = $user->tenant->subscriptions()->latest('id')->first();
        if ($subscription?->status === 'trial') {
            $subscription = $subscription->expireIfNeeded();
        }
        if ($subscription?->status === 'expired') {
            TrialAccount::query()->where('subscription_id', $subscription->id)
                ->where('status', TrialAccount::STATUS_ACTIVE)
                ->update(['status' => TrialAccount::STATUS_EXPIRED]);
            abort(Response::HTTP_PAYMENT_REQUIRED, 'Your trial has ended. Please contact ArtFlow to activate a subscription.');
        }

        return $next($request);
    }
}
