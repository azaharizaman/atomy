<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| HRM API Routes
|--------------------------------------------------------------------------
|
| Human Resource Management API endpoints for the Nexus ERP system.
| All routes are prefixed with /api/hrm and require authentication.
|
*/

Route::prefix('hrm')->middleware(['auth:sanctum', 'tenant.scope'])->group(function () {
    
    // Employee Management
    Route::prefix('employees')->group(function () {
        Route::get('/', [\App\Http\Controllers\Hrm\EmployeeController::class, 'index']);
        Route::post('/', [\App\Http\Controllers\Hrm\EmployeeController::class, 'store']);
        Route::get('/{id}', [\App\Http\Controllers\Hrm\EmployeeController::class, 'show']);
        Route::put('/{id}', [\App\Http\Controllers\Hrm\EmployeeController::class, 'update']);
        Route::delete('/{id}', [\App\Http\Controllers\Hrm\EmployeeController::class, 'destroy']);
        Route::post('/{id}/confirm', [\App\Http\Controllers\Hrm\EmployeeController::class, 'confirm']);
        Route::post('/{id}/terminate', [\App\Http\Controllers\Hrm\EmployeeController::class, 'terminate']);
    });

    // Leave Management
    Route::prefix('leaves')->group(function () {
        Route::get('/', [\App\Http\Controllers\Hrm\LeaveController::class, 'index']);
        Route::post('/', [\App\Http\Controllers\Hrm\LeaveController::class, 'store']);
        Route::get('/{id}', [\App\Http\Controllers\Hrm\LeaveController::class, 'show']);
        Route::post('/{id}/approve', [\App\Http\Controllers\Hrm\LeaveController::class, 'approve']);
        Route::post('/{id}/reject', [\App\Http\Controllers\Hrm\LeaveController::class, 'reject']);
        Route::post('/{id}/cancel', [\App\Http\Controllers\Hrm\LeaveController::class, 'cancel']);
    });

    // Attendance Management
    Route::prefix('attendance')->group(function () {
        Route::get('/', [\App\Http\Controllers\Hrm\AttendanceController::class, 'index']);
        Route::post('/clock-in', [\App\Http\Controllers\Hrm\AttendanceController::class, 'clockIn']);
        Route::post('/clock-out', [\App\Http\Controllers\Hrm\AttendanceController::class, 'clockOut']);
        Route::get('/{id}', [\App\Http\Controllers\Hrm\AttendanceController::class, 'show']);
    });

    // Performance Reviews
    Route::prefix('performance-reviews')->group(function () {
        Route::get('/', [\App\Http\Controllers\Hrm\PerformanceReviewController::class, 'index']);
        Route::post('/', [\App\Http\Controllers\Hrm\PerformanceReviewController::class, 'store']);
        Route::get('/{id}', [\App\Http\Controllers\Hrm\PerformanceReviewController::class, 'show']);
        Route::put('/{id}', [\App\Http\Controllers\Hrm\PerformanceReviewController::class, 'update']);
        Route::post('/{id}/submit', [\App\Http\Controllers\Hrm\PerformanceReviewController::class, 'submit']);
        Route::post('/{id}/complete', [\App\Http\Controllers\Hrm\PerformanceReviewController::class, 'complete']);
    });

    // Disciplinary Cases
    Route::prefix('disciplinary-cases')->group(function () {
        Route::get('/', [\App\Http\Controllers\Hrm\DisciplinaryController::class, 'index']);
        Route::post('/', [\App\Http\Controllers\Hrm\DisciplinaryController::class, 'store']);
        Route::get('/{id}', [\App\Http\Controllers\Hrm\DisciplinaryController::class, 'show']);
        Route::put('/{id}', [\App\Http\Controllers\Hrm\DisciplinaryController::class, 'update']);
        Route::post('/{id}/investigate', [\App\Http\Controllers\Hrm\DisciplinaryController::class, 'investigate']);
        Route::post('/{id}/resolve', [\App\Http\Controllers\Hrm\DisciplinaryController::class, 'resolve']);
        Route::post('/{id}/close', [\App\Http\Controllers\Hrm\DisciplinaryController::class, 'close']);
    });

    // Training Management
    Route::prefix('trainings')->group(function () {
        Route::get('/', [\App\Http\Controllers\Hrm\TrainingController::class, 'index']);
        Route::post('/', [\App\Http\Controllers\Hrm\TrainingController::class, 'store']);
        Route::get('/{id}', [\App\Http\Controllers\Hrm\TrainingController::class, 'show']);
        Route::put('/{id}', [\App\Http\Controllers\Hrm\TrainingController::class, 'update']);
        Route::post('/{id}/enroll', [\App\Http\Controllers\Hrm\TrainingController::class, 'enroll']);
        Route::post('/{trainingId}/enrollments/{enrollmentId}/complete', [\App\Http\Controllers\Hrm\TrainingController::class, 'completeEnrollment']);
    });
});
