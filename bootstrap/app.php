<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
        ]);
        
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'active-user' => \App\Http\Middleware\EnsureUserIsActive::class,
            'unit' => \App\Http\Middleware\EnsureActiveUnit::class,
            'consolidated' => \App\Http\Middleware\EnsureCanViewConsolidated::class,
            'purchase-request-unit' => \App\Http\Middleware\EnsurePurchaseRequestUnit::class,
        ]);

        // Model rute harus dicari di database unit aktif.
        $middleware->prependToPriorityList(
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \App\Http\Middleware\EnsureActiveUnit::class,
        );
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
