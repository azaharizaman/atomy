<?php

declare(strict_types=1);

use App\Http\Controllers\Api\FinanceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Finance API Routes
|--------------------------------------------------------------------------
|
| Routes for managing chart of accounts, journal entries, and general ledger.
|
*/

// Chart of Accounts
Route::prefix('accounts')->group(function () {
    Route::get('/', [FinanceController::class, 'listAccounts']);
    Route::get('/{id}', [FinanceController::class, 'getAccount']);
    Route::get('/{id}/balance', [FinanceController::class, 'getAccountBalance']);
    Route::get('/{id}/activity', [FinanceController::class, 'getAccountActivity']);
});

// Journal Entries
Route::prefix('journal-entries')->group(function () {
    Route::post('/', [FinanceController::class, 'createJournalEntry']);
    Route::get('/{id}', [FinanceController::class, 'getJournalEntry']);
    Route::post('/{id}/post', [FinanceController::class, 'postJournalEntry']);
    Route::post('/{id}/reverse', [FinanceController::class, 'reverseJournalEntry']);
    Route::delete('/{id}', [FinanceController::class, 'deleteJournalEntry']);
});

// Ledger Reports
Route::prefix('ledger')->group(function () {
    Route::get('/trial-balance', [FinanceController::class, 'getTrialBalance']);
    Route::get('/general-ledger', [FinanceController::class, 'getGeneralLedger']);
});
