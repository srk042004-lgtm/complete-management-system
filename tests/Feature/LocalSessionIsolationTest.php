<?php

namespace Tests\Feature;

use App\Http\Middleware\ScopeLocalSessionCookie;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class LocalSessionIsolationTest extends TestCase
{
    public function test_localhost_sessions_use_different_cookies_for_different_ports(): void
    {
        $middleware = new ScopeLocalSessionCookie;
        $next = static fn (): Response => new Response;

        $middleware->handle(Request::create('http://localhost:7600/portal'), $next);
        $port7600Cookie = config('session.cookie');
        $middleware->handle(Request::create('http://localhost/portal'), $next);
        $port80Cookie = config('session.cookie');

        $this->assertNotSame($port7600Cookie, $port80Cookie);
    }
}
