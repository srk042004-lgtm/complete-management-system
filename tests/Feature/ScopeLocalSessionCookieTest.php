<?php

namespace Tests\Feature;

use Symfony\Component\HttpFoundation\Cookie;
use Tests\TestCase;

class ScopeLocalSessionCookieTest extends TestCase
{
    public function test_local_session_cookie_name_is_stable_across_ports(): void
    {
        $firstResponse = $this->call('GET', '/hotel-admin/login', server: ['HTTP_HOST' => 'localhost:8000']);
        $secondResponse = $this->call('GET', '/hotel-admin/login', server: ['HTTP_HOST' => 'localhost:8001']);

        $firstSessionCookie = $this->sessionCookieName($firstResponse->headers->getCookies());
        $secondSessionCookie = $this->sessionCookieName($secondResponse->headers->getCookies());

        $this->assertNotNull($firstSessionCookie);
        $this->assertSame($firstSessionCookie, $secondSessionCookie);
    }

    /**
     * @param  array<Cookie>  $cookies
     */
    private function sessionCookieName(array $cookies): ?string
    {
        foreach ($cookies as $cookie) {
            if (str_starts_with($cookie->getName(), (string) config('session.cookie_base'))) {
                return $cookie->getName();
            }
        }

        return null;
    }
}
