<?php

use App\Http\Middleware\CheckRole;
use App\Http\Middleware\EnsureHotelAdmin;
use App\Http\Middleware\EnsureHotelBillingAccess;
use App\Http\Middleware\EnsureHotelCapability;
use App\Http\Middleware\EnsureHotelFeature;
use App\Http\Middleware\EnsureHotelRole;
use App\Http\Middleware\EnsureHotelTenantContext;
use App\Http\Middleware\EnsureSuperAdmin;
use App\Http\Middleware\InjectThemeSwitcher;
use App\Http\Middleware\ScopeLocalSessionCookie;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(InjectThemeSwitcher::class);
        $middleware->web(prepend: [ScopeLocalSessionCookie::class]);
        $middleware->alias([
            'super-admin' => EnsureSuperAdmin::class,
            'hotel-admin' => EnsureHotelAdmin::class,
            'hotel-role' => EnsureHotelRole::class,
            'hotel-tenant' => EnsureHotelTenantContext::class,
            'hotel-capability' => EnsureHotelCapability::class,
            'hotel-billing' => EnsureHotelBillingAccess::class,
            'hotel-feature' => EnsureHotelFeature::class,
            'check-role' => CheckRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
