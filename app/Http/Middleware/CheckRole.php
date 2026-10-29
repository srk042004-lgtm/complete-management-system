<?php

namespace App\Http\Middleware;

use App\Support\HotelRoleCatalog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Generic role gate for hotel workspace routes: route('...')->middleware('check-role:admin,receptionist').
 * Platform-level access is handled by EnsureSuperAdmin; this covers staff roles inside a hotel.
 */
class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();
        $userRole = $user ? HotelRoleCatalog::normalize((string) $user->role) : null;
        $allowed = array_map(fn (string $role): string => HotelRoleCatalog::normalize($role), $roles);

        abort_unless($user !== null && $userRole !== null && in_array($userRole, $allowed, true), Response::HTTP_FORBIDDEN, 'This area is not available for your role.');
        abort_unless($user->tenant !== null && $user->tenant->status === 'active', Response::HTTP_FORBIDDEN, 'This hotel account is not currently active.');

        return $next($request);
    }
}
