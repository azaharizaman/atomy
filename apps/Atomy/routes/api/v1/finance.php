<?php

declare(strict_types=1);

use App\Http\Controllers\FinanceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Finance API Routes
|--------------------------------------------------------------------------
|
| RESTful API routes for Finance (General Ledger) operations.
| All routes are protected by auth:sanctum and hierarchical feature flags.
|
*/

Route::middleware(['auth:sanctum'])->group(function () {
    
    // Account Management Routes
    Route::prefix('accounts')->group(function () {
        Route::get('/', [FinanceController::class, 'listAccounts'])
            ->middleware('feature:features.finance.account.read')
            ->name('finance.accounts.index');
            
        Route::post('/', [FinanceController::class, 'createAccount'])
            ->middleware('feature:features.finance.account.create')
            ->name('finance.accounts.store');
            
        Route::get('/{id}', [FinanceController::class, 'getAccount'])
            ->middleware('feature:features.finance.account.read')
            ->name('finance.accounts.show');
            
        Route::put('/{id}', [FinanceController::class, 'updateAccount'])
            ->middleware('feature:features.finance.account.update')
            ->name('finance.accounts.update');
            
        Route::delete('/{id}', [FinanceController::class, 'deleteAccount'])
            ->middleware('feature:features.finance.account.delete')
            ->name('finance.accounts.destroy');
            
        Route::get('/code/{code}', [FinanceController::class, 'getAccountByCode'])
            ->middleware('feature:features.finance.account.read')
            ->name('finance.accounts.by-code');
            
        Route::get('/{id}/balance', [FinanceController::class, 'getAccountBalance'])
            ->middleware('feature:features.finance.account.read')
            ->name('finance.accounts.balance');
    });

    // Journal Entry Management Routes
    Route::prefix('journal-entries')->group(function () {
        Route::get('/', [FinanceController::class, 'listJournalEntries'])
            ->middleware('feature:features.finance.journal_entry.read')
            ->name('finance.journal-entries.index');
            
        Route::post('/', [FinanceController::class, 'createJournalEntry'])
            ->middleware('feature:features.finance.journal_entry.create')
            ->name('finance.journal-entries.store');
            
        Route::get('/{id}', [FinanceController::class, 'getJournalEntry'])
            ->middleware('feature:features.finance.journal_entry.read')
            ->name('finance.journal-entries.show');
            
        Route::put('/{id}', [FinanceController::class, 'updateJournalEntry'])
            ->middleware('feature:features.finance.journal_entry.update')
            ->name('finance.journal-entries.update');
            
        Route::delete('/{id}', [FinanceController::class, 'deleteJournalEntry'])
            ->middleware('feature:features.finance.journal_entry.delete')
            ->name('finance.journal-entries.destroy');
            
        Route::post('/{id}/post', [FinanceController::class, 'postJournalEntry'])
            ->middleware('feature:features.finance.journal_entry.post')
            ->name('finance.journal-entries.post');
            
        Route::post('/{id}/reverse', [FinanceController::class, 'reverseJournalEntry'])
            ->middleware('feature:features.finance.journal_entry.reverse')
            ->name('finance.journal-entries.reverse');
            
        Route::get('/number/{entryNumber}', [FinanceController::class, 'getJournalEntryByNumber'])
            ->middleware('feature:features.finance.journal_entry.read')
            ->name('finance.journal-entries.by-number');
    });
    
    // Chart of Accounts Route
    Route::get('/chart-of-accounts', [FinanceController::class, 'getChartOfAccounts'])
        ->middleware('feature:features.finance.account.read')
        ->name('finance.chart-of-accounts');
});
