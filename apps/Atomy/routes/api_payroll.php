<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Payroll API Routes
|--------------------------------------------------------------------------
|
| Payroll processing API endpoints for the Nexus ERP system.
| All routes are prefixed with /api/payroll and require authentication.
|
*/

Route::prefix('payroll')->middleware(['auth:sanctum', 'tenant.scope'])->group(function () {
    
    // Payroll Component Management
    Route::prefix('components')->group(function () {
        Route::get('/', [\App\Http\Controllers\Payroll\ComponentController::class, 'index']);
        Route::post('/', [\App\Http\Controllers\Payroll\ComponentController::class, 'store']);
        Route::get('/{id}', [\App\Http\Controllers\Payroll\ComponentController::class, 'show']);
        Route::put('/{id}', [\App\Http\Controllers\Payroll\ComponentController::class, 'update']);
        Route::delete('/{id}', [\App\Http\Controllers\Payroll\ComponentController::class, 'destroy']);
    });

    // Payroll Processing
    Route::prefix('process')->group(function () {
        Route::post('/period', [\App\Http\Controllers\Payroll\PayrollController::class, 'processPeriod']);
        Route::post('/employee/{employeeId}', [\App\Http\Controllers\Payroll\PayrollController::class, 'processEmployee']);
    });

    // Payslip Management
    Route::prefix('payslips')->group(function () {
        Route::get('/', [\App\Http\Controllers\Payroll\PayslipController::class, 'index']);
        Route::get('/{id}', [\App\Http\Controllers\Payroll\PayslipController::class, 'show']);
        Route::post('/{id}/approve', [\App\Http\Controllers\Payroll\PayslipController::class, 'approve']);
        Route::post('/{id}/mark-paid', [\App\Http\Controllers\Payroll\PayslipController::class, 'markPaid']);
        Route::get('/employee/{employeeId}', [\App\Http\Controllers\Payroll\PayslipController::class, 'byEmployee']);
    });
});
