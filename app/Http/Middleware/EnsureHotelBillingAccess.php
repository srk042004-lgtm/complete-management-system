<?php

namespace App\Http\Middleware;

use App\Support\HotelRoleCatalog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureHotelBillingAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return redirect()->route('hotel-admin.login');
        }

        abort_unless(
            $user->status === 'active'
                && $user->tenant?->status === 'active'
                && HotelRoleCatalog::normalize((string) $user->role) === 'owner',
            Response::HTTP_FORBIDDEN,
            'Only an active hotel owner can manage subscription billing.'
        );

        return $next($request);
    }
}
