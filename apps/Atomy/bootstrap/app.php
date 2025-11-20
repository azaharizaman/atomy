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
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->withProviders([
        App\Providers\IdentityServiceProvider::class,
        App\Providers\CryptoServiceProvider::class,
        App\Providers\SchedulerServiceProvider::class,
        App\Providers\IntelligenceServiceProvider::class,
        App\Providers\ProcurementServiceProvider::class,
        App\Providers\PayableServiceProvider::class,
        App\Providers\PartyServiceProvider::class,
        App\Providers\ProductServiceProvider::class,
    ])
    ->create();
