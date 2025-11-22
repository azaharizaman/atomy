<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\ArchivalPolicyController;
use App\Http\Controllers\Admin\FeatureFlagController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin API Routes
|--------------------------------------------------------------------------
|
| Feature flag management and archival policy configuration.
| All routes require 'admin' role authorization.
|
*/

Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    // Feature Flag Management
    Route::prefix('features')->group(function () {
        Route::get('/', [FeatureFlagController::class, 'index'])
            ->name('admin.features.index');
        
        Route::post('/{flag}/enable', [FeatureFlagController::class, 'enable'])
            ->name('admin.features.enable')
            ->where('flag', '.*');
        
        Route::post('/{flag}/disable', [FeatureFlagController::class, 'disable'])
            ->name('admin.features.disable')
            ->where('flag', '.*');
        
        Route::get('/{flag}/check', [FeatureFlagController::class, 'check'])
            ->name('admin.features.check')
            ->where('flag', '.*');
        
        Route::get('/{flag}/audit', [FeatureFlagController::class, 'audit'])
            ->name('admin.features.audit')
            ->where('flag', '.*');
        
        // Orphaned Data Management
        Route::get('/orphans', [FeatureFlagController::class, 'getOrphans'])
            ->name('admin.features.orphans');
        
        Route::post('/orphans/archive', [FeatureFlagController::class, 'archiveOrphans'])
            ->name('admin.features.orphans.archive');
    });
    
    // Archival Policy Management
    Route::prefix('archival-policy')->group(function () {
        Route::get('/', [ArchivalPolicyController::class, 'show'])
            ->name('admin.archival-policy.show');
        
        Route::put('/', [ArchivalPolicyController::class, 'update'])
            ->name('admin.archival-policy.update');
    });
});
