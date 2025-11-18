<?php

use App\Http\Controllers\Api\PeriodController;
use Illuminate\Support\Facades\Route;

Route::prefix('periods')->group(function () {
    // List periods
    Route::get('/', [PeriodController::class, 'index']);
    
    // Get open period
    Route::get('/open', [PeriodController::class, 'openPeriod']);
    
    // Check posting allowed
    Route::post('/check-posting', [PeriodController::class, 'checkPosting']);
    
    // Get specific period
    Route::get('/{id}', [PeriodController::class, 'show']);
    
    // Close period
    Route::post('/{id}/close', [PeriodController::class, 'close']);
    
    // Reopen period
    Route::post('/{id}/reopen', [PeriodController::class, 'reopen']);
});
