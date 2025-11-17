<?php

use App\Http\Controllers\AuthenticationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Identity API Routes
|--------------------------------------------------------------------------
|
| Authentication, authorization, and identity management endpoints.
|
*/

// Public routes
Route::post('/auth/login', [AuthenticationController::class, 'login']);

// Protected routes (require authentication)
Route::middleware(['identity.auth'])->group(function () {
    Route::post('/auth/logout', [AuthenticationController::class, 'logout']);
    
    // API Token management
    Route::get('/auth/tokens', [AuthenticationController::class, 'listTokens']);
    Route::post('/auth/tokens', [AuthenticationController::class, 'createToken']);
    Route::delete('/auth/tokens/{tokenId}', [AuthenticationController::class, 'revokeToken']);
    
    // Session management
    Route::get('/auth/sessions', [AuthenticationController::class, 'listSessions']);
    Route::delete('/auth/sessions', [AuthenticationController::class, 'revokeAllSessions']);
});
