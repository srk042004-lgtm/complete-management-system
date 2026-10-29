<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureHotelFeature
{
    public function handle(Request $request, Closure $next, string $feature): mixed
    {
        $user = $request->user();
        abort_unless($user !== null && $user->tenant !== null, Response::HTTP_FORBIDDEN);
        abort_unless($user->tenant->hasFeature($feature), Response::HTTP_FORBIDDEN, 'This module is disabled for your hotel. Ask your hotel administrator to enable it in Hotel Features.');

        return $next($request);
    }
}
