<?php

namespace App\Http\Middleware;

use App\Models\TrialAccount;
use App\Support\HotelRoleCatalog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureHotelAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return redirect()->route('hotel-admin.login');
        }

        $normalizedRole = HotelRoleCatalog::normalize((string) $user->role);

        if (! in_array($normalizedRole, ['admin', 'owner', 'manager', 'receptionist', 'housekeeping', 'accountant', 'corporate', 'maintenance'], true) || $user->status !== 'active') {
            abort(Response::HTTP_FORBIDDEN, 'Active Hotel Admin access is required.');
        }

        if ($user->tenant === null || $user->tenant->status !== 'active') {
            abort(Response::HTTP_FORBIDDEN, 'This hotel account is not currently active.');
        }

        $subscription = $user->tenant->subscriptions()->latest('id')->first();
        if ($subscription?->status === 'trial') {
            $subscription = $subscription->expireIfNeeded();
        }
        // Billing pages stay reachable so an expired owner can renew.
        if ($subscription?->status === 'expired' && ! $request->routeIs('account.*')) {
            TrialAccount::query()->where('subscription_id', $subscription->id)
                ->where('status', TrialAccount::STATUS_ACTIVE)
                ->update(['status' => TrialAccount::STATUS_EXPIRED]);
            abort(Response::HTTP_PAYMENT_REQUIRED, 'Your 10-day trial has ended. Please contact ArtFlow to activate a subscription.');
        }

        return $next($request);
    }
}
