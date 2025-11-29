<?php

use App\Http\Controllers\Settings\FeatureFlagController;
use App\Http\Controllers\Settings\PasswordController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\TwoFactorAuthenticationController;
use App\Http\Controllers\Settings\UserFlagController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware('auth')->group(function () {
    Route::redirect('settings', '/settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('settings/password', [PasswordController::class, 'edit'])->name('user-password.edit');

    Route::put('settings/password', [PasswordController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('user-password.update');

    Route::get('settings/appearance', function () {
        return Inertia::render('settings/Appearance');
    })->name('appearance.edit');

    Route::get('settings/two-factor', [TwoFactorAuthenticationController::class, 'show'])
        ->name('two-factor.show');

    // ==========================================
    // Feature Flags Settings
    // ==========================================
    Route::prefix('settings/feature-flags')->name('feature-flags.')->group(function () {
        // Feature Flags UI Page
        Route::get('/', [FeatureFlagController::class, 'index'])->name('index');

        // Feature Flag CRUD
        Route::get('/list', [FeatureFlagController::class, 'list'])->name('list');
        Route::post('/', [FeatureFlagController::class, 'store'])->name('store');
        Route::get('/{id}', [FeatureFlagController::class, 'show'])->name('show');
        Route::put('/{id}', [FeatureFlagController::class, 'update'])->name('update');
        Route::delete('/{id}', [FeatureFlagController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/toggle', [FeatureFlagController::class, 'toggle'])->name('toggle');

        // Flag checking
        Route::get('/check/{name}', [FeatureFlagController::class, 'check'])->name('check');
        Route::get('/enabled', [FeatureFlagController::class, 'enabledFlags'])->name('enabled');
    });

    // ==========================================
    // User Flag Overrides
    // ==========================================
    Route::prefix('settings/user-flags')->name('user-flags.')->group(function () {
        // Current user's overrides
        Route::get('/my', [UserFlagController::class, 'myOverrides'])->name('my');

        // User-specific overrides
        Route::get('/user/{userId}', [UserFlagController::class, 'listForUser'])->name('user');
        Route::get('/user/{userId}/active', [UserFlagController::class, 'activeForUser'])->name('user.active');
        Route::delete('/user/{userId}', [UserFlagController::class, 'deleteAllForUser'])->name('user.delete-all');

        // Flag-specific overrides
        Route::get('/flag/{flagName}', [UserFlagController::class, 'listForFlag'])->name('flag');

        // CRUD operations
        Route::get('/{id}', [UserFlagController::class, 'show'])->name('show');
        Route::put('/{id}', [UserFlagController::class, 'update'])->name('update');
        Route::delete('/{id}', [UserFlagController::class, 'destroy'])->name('destroy');

        // Upsert for user+flag combination
        Route::put('/user/{userId}/flag/{flagName}', [UserFlagController::class, 'upsert'])->name('upsert');
        Route::delete('/user/{userId}/flag/{flagName}', [UserFlagController::class, 'deleteByUserAndFlag'])->name('delete-by-user-flag');
        Route::get('/user/{userId}/flag/{flagName}/check', [UserFlagController::class, 'checkForUser'])->name('check');

        // Cleanup expired
        Route::delete('/expired', [UserFlagController::class, 'deleteExpired'])->name('delete-expired');
    });
});
