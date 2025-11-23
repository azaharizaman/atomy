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
        // Core Service Providers
        App\Providers\TenantServiceProvider::class,
        
        // Domain Service Providers
        // App\Providers\EventServiceProvider::class,
        App\Providers\IdentityServiceProvider::class,
        App\Providers\CryptoServiceProvider::class,
        App\Providers\SchedulerServiceProvider::class,
        App\Providers\IntelligenceServiceProvider::class,
        App\Providers\ProcurementServiceProvider::class,
        App\Providers\PayableServiceProvider::class,
        App\Providers\ReceivableServiceProvider::class,
        App\Providers\PartyServiceProvider::class,
        App\Providers\ProductServiceProvider::class,
        App\Providers\BudgetServiceProvider::class,
        App\Providers\AnalyticsServiceProvider::class,
        // App\Providers\ReportingServiceProvider::class,  // TODO: Complete Export/Import packages first
        App\Providers\InventoryServiceProvider::class,
        App\Providers\WarehouseServiceProvider::class,
        App\Providers\FieldServiceProvider::class,
        // App\Providers\FinanceServiceProvider::class,
    ])
    ->create();
