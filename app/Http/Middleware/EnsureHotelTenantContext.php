<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureHotelTenantContext
{
    private const SESSION_KEY = 'hotel_tenant_id';

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        abort_unless($user !== null && $user->tenant_id !== null, Response::HTTP_FORBIDDEN, 'An authenticated hotel tenant is required.');

        $tenantId = (int) $user->tenant_id;
        $sessionTenantId = $request->session()->get(self::SESSION_KEY);

        if ($sessionTenantId !== null && (int) $sessionTenantId !== $tenantId) {
            abort(Response::HTTP_FORBIDDEN, 'The authenticated session does not match the hotel tenant.');
        }

        if ($sessionTenantId === null) {
            $request->session()->put(self::SESSION_KEY, $tenantId);
        }

        $request->attributes->set(self::SESSION_KEY, $tenantId);

        return $next($request);
    }
}
