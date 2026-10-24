<?php

use App\Http\Middleware\EnsureAdminAuthenticated;
use App\Http\Middleware\EnsureCompanyAuthenticated;
use App\Http\Middleware\JsonRes;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\SiteMaintenance;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Register authentication middleware aliases.
        $middleware->alias([
            'admin.auth' => EnsureAdminAuthenticated::class,
            'company.auth' => EnsureCompanyAuthenticated::class,
            'json.res' => JsonRes::class,
            'site.maintenance' => SiteMaintenance::class,
        ]);

        // A logged-in company opening a guest-only page (e.g. company sign-up) goes to
        // its dashboard. Without this Laravel falls back to the home page, which looks
        // like the link does nothing.
        $middleware->redirectUsersTo(fn() => auth('company')->check()
            ? route('company.dashboard')
            : route('home'));

        // Apply locale handling to web routes only.
        $middleware->web(append: [
            SetLocale::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->create();
