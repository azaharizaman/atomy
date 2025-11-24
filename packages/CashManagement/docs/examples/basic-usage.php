<?php

declare(strict_types=1);

/**
 * Basic Usage Example: CashManagement
 * 
 * This example demonstrates:
 * 1. Creating a bank account
 * 2. Importing a bank statement from CSV
 * 3. Auto-reconciling transactions
 * 4. Reviewing pending adjustments
 * 5. Posting to GL or rejecting
 */

use Nexus\CashManagement\Contracts\CashManagementManagerInterface;
use Nexus\CashManagement\Contracts\PendingAdjustmentRepositoryInterface;
use Nexus\CashManagement\Enums\BankAccountType;
use Nexus\Import\Contracts\ImportManagerInterface;

// ============================================
// Step 1: Create a Bank Account
// ============================================

$cashManager = app(CashManagementManagerInterface::class);

$bankAccount = $cashManager->createBankAccount(
    tenantId: 'tenant_abc123',
    accountCode: '1000-01',
    glAccountId: 'gl_cash_in_bank',
    accountNumber: '1234567890',
    bankName: 'Maybank',
    bankCode: 'MBB',
    accountType: BankAccountType::CHECKING,
    currency: 'MYR',
    csvImportConfig: [
        'date_column' => 'Transaction Date',
        'description_column' => 'Description',
        'debit_column' => 'Debit',
        'credit_column' => 'Credit',
        'balance_column' => 'Balance',
    ]
);

echo "✓ Bank Account Created: {$bankAccount->getAccountCode()}\n";

// ============================================
// Step 2: Import Bank Statement from CSV
// ============================================

$importManager = app(ImportManagerInterface::class);

// Parse CSV file using Nexus\Import
$csvFilePath = storage_path('uploads/maybank_statement_nov_2024.csv');

$importResult = $importManager->importFile(
    filePath: $csvFilePath,
    importType: 'bank_statement',
    config: $bankAccount->getCSVImportConfig()
);

echo "✓ CSV Parsed: {$importResult->getRowCount()} transactions\n";

// Create bank statement entity
$statement = $cashManager->importBankStatement(
    bankAccountId: $bankAccount->getId(),
    startDate: '2024-11-01',
    endDate: '2024-11-30',
    transactions: $importResult->getData(),
    importedBy: 'user_admin_001'
);

echo "✓ Bank Statement Imported: {$statement->getId()}\n";
echo "  Opening Balance: MYR {$statement->getOpeningBalance()}\n";
echo "  Closing Balance: MYR {$statement->getClosingBalance()}\n";
echo "  Total Transactions: {$statement->getTransactionCount()}\n";

// ============================================
// Step 3: Auto-Reconcile Statement
// ============================================

$reconciliationResult = $cashManager->reconcileStatement($statement->getId());

echo "✓ Auto-Reconciliation Complete:\n";
echo "  Matched: {$reconciliationResult->getMatchedCount()}\n";
echo "  Unmatched: {$reconciliationResult->getUnmatchedCount()}\n";
echo "  Variance Review: {$reconciliationResult->getVarianceCount()}\n";

// ============================================
// Step 4: Review Pending Adjustments
// ============================================

$pendingAdjustmentRepo = app(PendingAdjustmentRepositoryInterface::class);
$pendingAdjustments = $pendingAdjustmentRepo->findByTenant('tenant_abc123');

echo "\n✓ Pending Adjustments Requiring Review: " . count($pendingAdjustments) . "\n";

foreach ($pendingAdjustments as $adjustment) {
    echo "\n  Adjustment ID: {$adjustment->getId()}\n";
    echo "  Description: {$adjustment->getDescription()}\n";
    echo "  Amount: MYR {$adjustment->getAmount()}\n";
    echo "  AI Suggested Account: {$adjustment->getSuggestedGlAccount()}\n";
    echo "  AI Model Version: {$adjustment->getAiModelVersion()}\n";
}

// ============================================
// Step 5: Approve Pending Adjustment
// ============================================

// User approves and posts to GL
$firstAdjustment = $pendingAdjustments[0];

$journalEntryId = $cashManager->postPendingAdjustment(
    pendingAdjustmentId: $firstAdjustment->getId(),
    glAccount: '6200', // Bank Fees Expense (user confirms AI suggestion)
    postedBy: 'user_admin_001'
);

echo "\n✓ Adjustment Posted to GL: Journal Entry {$journalEntryId}\n";

// ============================================
// Step 6: Reject Pending Adjustment (Triggers Reversal)
// ============================================

if (count($pendingAdjustments) > 1) {
    $secondAdjustment = $pendingAdjustments[1];
    
    // User rejects (incorrect match)
    $cashManager->rejectPendingAdjustment(
        pendingAdjustmentId: $secondAdjustment->getId(),
        reason: 'Incorrect match - this is a customer deposit, not a bank fee',
        rejectedBy: 'user_admin_001'
    );
    
    echo "✓ Adjustment Rejected: Reversal workflow initiated\n";
    echo "  Payment application reversed (if exists)\n";
    echo "  GL reversal requires approval\n";
}

// ============================================
// Output Summary
// ============================================

echo "\n========================================\n";
echo "SUMMARY\n";
echo "========================================\n";
echo "Bank Account: {$bankAccount->getAccountCode()} ({$bankAccount->getBankName()})\n";
echo "Statement Period: {$statement->getStartDate()} to {$statement->getEndDate()}\n";
echo "Total Transactions: {$statement->getTransactionCount()}\n";
echo "Auto-Matched: {$reconciliationResult->getMatchedCount()}\n";
echo "Pending Review: {$reconciliationResult->getUnmatchedCount()}\n";
echo "Adjustments Posted: 1\n";
echo "Adjustments Rejected: 1\n";
echo "========================================\n";

// Expected output:
/*
✓ Bank Account Created: 1000-01
✓ CSV Parsed: 45 transactions
✓ Bank Statement Imported: 01HXYZ...
  Opening Balance: MYR 25000.00
  Closing Balance: MYR 28500.00
  Total Transactions: 45
✓ Auto-Reconciliation Complete:
  Matched: 40
  Unmatched: 5
  Variance Review: 0

✓ Pending Adjustments Requiring Review: 5

  Adjustment ID: 01HXYZ...
  Description: Bank fee - monthly service charge
  Amount: MYR 15.00
  AI Suggested Account: 6200
  AI Model Version: 1.2.3

✓ Adjustment Posted to GL: Journal Entry 01HXYZ...
✓ Adjustment Rejected: Reversal workflow initiated
  Payment application reversed (if exists)
  GL reversal requires approval

========================================
SUMMARY
========================================
Bank Account: 1000-01 (Maybank)
Statement Period: 2024-11-01 to 2024-11-30
Total Transactions: 45
Auto-Matched: 40
Pending Review: 5
Adjustments Posted: 1
Adjustments Rejected: 1
========================================
*/
