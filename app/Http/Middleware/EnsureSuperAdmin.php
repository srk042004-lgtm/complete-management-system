<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class EnsureSuperAdmin
{
    public function handle(Request $request, Closure $next): mixed
    {
        $user = $request->user('super_admin');

        if (
            ! $user
            || ! method_exists($user, 'getAttribute')
            || ! (bool) $user->is_active
            || $user->status !== 'active'
            || ! array_key_exists((string) $user->role, \App\Models\SuperAdmin::teamRoles())
        ) {
            abort(Response::HTTP_FORBIDDEN, 'Access denied. Super Admin role required.');
        }

        return $next($request);
    }
}
