<?php

use App\Http\Controllers\Api\AuditLogController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Audit Logger API Routes
|--------------------------------------------------------------------------
|
| RESTful API endpoints for audit log management
| Satisfies: FUN-AUD-0198
|
*/

Route::prefix('v1')->middleware(['api', 'auth:sanctum'])->group(function () {
    
    // Audit Log Routes
    Route::prefix('audit-logs')->group(function () {
        // List/search audit logs
        Route::get('/', [AuditLogController::class, 'index'])
            ->name('api.audit-logs.index');
        
        // Get audit statistics
        Route::get('/statistics', [AuditLogController::class, 'statistics'])
            ->name('api.audit-logs.statistics');
        
        // Export audit logs
        Route::get('/export', [AuditLogController::class, 'export'])
            ->name('api.audit-logs.export');
        
        // Get logs by batch UUID
        Route::get('/batch/{uuid}', [AuditLogController::class, 'batchLogs'])
            ->name('api.audit-logs.batch');
        
        // Get subject history
        Route::get('/subject/{type}/{id}', [AuditLogController::class, 'subjectHistory'])
            ->name('api.audit-logs.subject');
        
        // Get causer activity
        Route::get('/causer/{type}/{id}', [AuditLogController::class, 'causerActivity'])
            ->name('api.audit-logs.causer');
        
        // Get single audit log
        Route::get('/{id}', [AuditLogController::class, 'show'])
            ->name('api.audit-logs.show');
        
        // Create audit log (for system activities)
        Route::post('/', [AuditLogController::class, 'store'])
            ->name('api.audit-logs.store');
    });
    
});
