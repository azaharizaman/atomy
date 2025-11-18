<?php

declare(strict_types=1);

use App\Http\Controllers\Api\AccountingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Accounting API Routes
|--------------------------------------------------------------------------
|
| Routes for financial statement generation, period close operations,
| multi-entity consolidation, and budget variance analysis.
|
*/

// Financial Statements
Route::prefix('statements')->group(function () {
    // Generate statements
    Route::post('/balance-sheet', [AccountingController::class, 'generateBalanceSheet']);
    Route::post('/income-statement', [AccountingController::class, 'generateIncomeStatement']);
    Route::post('/cash-flow', [AccountingController::class, 'generateCashFlowStatement']);
    
    // Statement operations
    Route::get('/{id}', [AccountingController::class, 'getStatement']);
    Route::post('/{id}/export', [AccountingController::class, 'exportStatement']);
    Route::post('/{id}/lock', [AccountingController::class, 'lockStatement']);
    Route::post('/{id}/unlock', [AccountingController::class, 'unlockStatement']);
});

// Period Close Operations
Route::prefix('period-close')->group(function () {
    Route::post('/month', [AccountingController::class, 'closeMonth']);
    Route::post('/year', [AccountingController::class, 'closeYear']);
    Route::post('/{periodId}/reopen', [AccountingController::class, 'reopenPeriod']);
    Route::get('/{periodId}/status', [AccountingController::class, 'getPeriodCloseStatus']);
});

// Consolidation
Route::prefix('consolidation')->group(function () {
    Route::post('/consolidate', [AccountingController::class, 'consolidateStatements']);
    Route::get('/statements/{id}/entries', [AccountingController::class, 'getConsolidationEntries']);
});

// Variance Analysis
Route::prefix('variance')->group(function () {
    Route::post('/budget', [AccountingController::class, 'calculateBudgetVariance']);
    Route::post('/period', [AccountingController::class, 'calculatePeriodVariance']);
});
