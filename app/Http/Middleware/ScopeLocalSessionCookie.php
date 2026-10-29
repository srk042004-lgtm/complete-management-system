<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ScopeLocalSessionCookie
{
    public function handle(Request $request, Closure $next): Response
    {
        if (
            app()->environment(['local', 'testing'])
            && in_array($request->getHost(), ['localhost', '127.0.0.1', '::1'], true)
        ) {
            $baseName = (string) config('session.cookie_base', config('session.cookie'));
            $originHash = substr(hash('sha256', $request->getHttpHost()), 0, 12);

            config(['session.cookie' => "{$baseName}-local-{$originHash}"]);
        }

        return $next($request);
    }
}
