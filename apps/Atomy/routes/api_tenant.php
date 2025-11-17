<?php

declare(strict_types=1);

use App\Http\Controllers\Api\TenantController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Tenant API Routes
|--------------------------------------------------------------------------
|
| RESTful API for tenant management
|
*/

Route::middleware(['api', 'auth:sanctum'])->prefix('api/tenants')->group(function () {
    // Tenant CRUD
    Route::get('/', [TenantController::class, 'index'])->name('tenants.index');
    Route::post('/', [TenantController::class, 'store'])->name('tenants.store');
    Route::get('/{id}', [TenantController::class, 'show'])->name('tenants.show');
    Route::put('/{id}', [TenantController::class, 'update'])->name('tenants.update');
    Route::delete('/{id}', [TenantController::class, 'destroy'])->name('tenants.destroy');
    Route::delete('/{id}/force', [TenantController::class, 'forceDestroy'])->name('tenants.force-destroy');

    // Lifecycle Management
    Route::post('/{id}/activate', [TenantController::class, 'activate'])->name('tenants.activate');
    Route::post('/{id}/suspend', [TenantController::class, 'suspend'])->name('tenants.suspend');
    Route::post('/{id}/reactivate', [TenantController::class, 'reactivate'])->name('tenants.reactivate');

    // Statistics & Reporting
    Route::get('/statistics/overview', [TenantController::class, 'statistics'])->name('tenants.statistics');
    Route::get('/trials/active', [TenantController::class, 'trials'])->name('tenants.trials');
    Route::get('/trials/expired', [TenantController::class, 'expiredTrials'])->name('tenants.expired-trials');

    // Impersonation
    Route::post('/{id}/impersonate', [TenantController::class, 'impersonate'])->name('tenants.impersonate');
    Route::post('/impersonation/stop', [TenantController::class, 'stopImpersonation'])->name('tenants.stop-impersonation');
});
