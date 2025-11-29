<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Backoffice\UnitController;
use App\Http\Controllers\Api\Backoffice\StaffController;
use App\Http\Controllers\Api\Backoffice\OfficeController;
use App\Http\Controllers\Api\Backoffice\CompanyController;
use App\Http\Controllers\Api\Backoffice\TransferController;
use App\Http\Controllers\Api\Backoffice\DepartmentController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware(['auth:sanctum', 'verified'])
    ->prefix('backoffice')
    ->name('backoffice.')
    ->group(function () {
        Route::apiResource('companies', CompanyController::class);
        Route::apiResource('offices', OfficeController::class);
        Route::apiResource('departments', DepartmentController::class);
        Route::apiResource('staff', StaffController::class);
        Route::apiResource('units', UnitController::class);
        
        Route::apiResource('transfers', TransferController::class)->except(['update', 'destroy']);
        Route::post('transfers/{id}/approve', [TransferController::class, 'approve']);
        Route::post('transfers/{id}/reject', [TransferController::class, 'reject']);
        Route::post('transfers/{id}/cancel', [TransferController::class, 'cancel']);
        Route::post('transfers/{id}/complete', [TransferController::class, 'complete']);
    });
