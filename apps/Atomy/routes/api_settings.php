<?php

use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Settings API Routes
|--------------------------------------------------------------------------
|
| API routes for managing application, tenant, and user settings.
| All routes are scoped to /api/settings
|
*/

// Get setting with hierarchical resolution
Route::get('/{key}', [SettingsController::class, 'get'])
    ->where('key', '.*')
    ->name('settings.get');

// Get all settings for current scope
Route::get('/', [SettingsController::class, 'index'])
    ->name('settings.index');

// Get settings by prefix
Route::get('/prefix/{prefix}', [SettingsController::class, 'getByPrefix'])
    ->where('prefix', '.*')
    ->name('settings.get-by-prefix');

// Get setting metadata
Route::get('/metadata/{key}', [SettingsController::class, 'getMetadata'])
    ->where('key', '.*')
    ->name('settings.get-metadata');

// Get setting origin (which layer it came from)
Route::get('/origin/{key}', [SettingsController::class, 'getOrigin'])
    ->where('key', '.*')
    ->name('settings.get-origin');

// Set user setting
Route::post('/user', [SettingsController::class, 'setUserSetting'])
    ->name('settings.set-user');

// Set tenant setting (admin only)
Route::post('/tenant', [SettingsController::class, 'setTenantSetting'])
    ->middleware('can:manage-tenant-settings')
    ->name('settings.set-tenant');

// Bulk update settings
Route::post('/bulk', [SettingsController::class, 'bulkSet'])
    ->name('settings.bulk-set');

// Delete user setting
Route::delete('/user/{key}', [SettingsController::class, 'deleteUserSetting'])
    ->where('key', '.*')
    ->name('settings.delete-user');

// Delete tenant setting (admin only)
Route::delete('/tenant/{key}', [SettingsController::class, 'deleteTenantSetting'])
    ->where('key', '.*')
    ->middleware('can:manage-tenant-settings')
    ->name('settings.delete-tenant');

// Export tenant settings
Route::get('/export/tenant', [SettingsController::class, 'exportTenant'])
    ->middleware('can:manage-tenant-settings')
    ->name('settings.export-tenant');

// Import tenant settings
Route::post('/import/tenant', [SettingsController::class, 'importTenant'])
    ->middleware('can:manage-tenant-settings')
    ->name('settings.import-tenant');

// Setting history
Route::get('/history/{key}', [SettingsController::class, 'history'])
    ->where('key', '.*')
    ->name('settings.history');

// Cache management
Route::post('/cache/flush', [SettingsController::class, 'flushCache'])
    ->middleware('can:manage-cache')
    ->name('settings.cache.flush');

Route::delete('/cache/{key}', [SettingsController::class, 'forgetCache'])
    ->where('key', '.*')
    ->middleware('can:manage-cache')
    ->name('settings.cache.forget');
