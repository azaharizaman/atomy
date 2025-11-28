<?php

declare(strict_types=1);

/**
 * Basic Usage Example: Budget
 * 
 * This example demonstrates:
 * 1. Creating a budget
 * 2. Checking budget availability
 * 3. Committing budget amount (encumbrance)
 * 4. Recording actual expenditure
 * 5. Calculating variance
 */

use Nexus\Budget\Contracts\BudgetManagerInterface;
use Nexus\Budget\Enums\BudgetType;
use Nexus\Budget\Enums\BudgetStatus;

// ============================================
// Step 1: Create a Budget
// ============================================

// Assume $budgetManager is injected via DI
$budgetManager = app(BudgetManagerInterface::class);

$budget = $budgetManager->createBudget(
    tenantId: 'tenant-abc123',
    periodId: 'FY2025-Q1',
    departmentId: 'dept-IT',
    accountId: 'acc-software-licenses',
    budgetType: BudgetType::OperatingExpense,
    allocatedAmount: 50000.00,
    baseCurrency: 'MYR',
    presentationCurrency: 'USD',
    exchangeRate: 4.50,
    parentBudgetId: null
);

echo "Budget created: {$budget->getId()}\n";
echo "Allocated: {$budget->getAllocatedAmount()} {$budget->getBaseCurrency()}\n";

// ============================================
// Step 2: Check Budget Availability
// ============================================

$requestedAmount = 12500.00;

$availability = $budgetManager->checkAvailability(
    budgetId: $budget->getId(),
    amount: $requestedAmount
);

if ($availability->isAvailable) {
    echo "✓ Budget available for {$requestedAmount}\n";
    echo "Available amount: {$availability->availableAmount}\n";
} else {
    echo "✗ Insufficient budget\n";
    echo "Recommended action: {$availability->recommendedAction}\n";
    exit(1);
}

// ============================================
// Step 3: Commit Budget Amount (Encumbrance)
// ============================================

// When a Purchase Order is approved, commit the budget
$budgetManager->commitAmount(
    budgetId: $budget->getId(),
    amount: $requestedAmount,
    sourceDocumentId: 'PO-2025-0001',
    sourceDocumentType: 'purchase_order',
    description: 'Microsoft Office 365 licenses for 25 users'
);

echo "Budget committed: {$requestedAmount} for PO-2025-0001\n";

// ============================================
// Step 4: Record Actual Expenditure
// ============================================

// When the invoice is posted to GL, record actual
$actualAmount = 12350.00; // Slightly less due to discount

$budgetManager->recordActual(
    budgetId: $budget->getId(),
    amount: $actualAmount,
    sourceDocumentId: 'JE-2025-0042',
    sourceDocumentType: 'journal_entry',
    description: 'Microsoft invoice posted - 2% early payment discount'
);

echo "Actual recorded: {$actualAmount} from JE-2025-0042\n";

// ============================================
// Step 5: Calculate Variance
// ============================================

$variance = $budgetManager->calculateVariance($budget->getId());

echo "\n=== Budget Variance Analysis ===\n";
echo "Absolute Variance: {$variance->getAbsoluteVariance()}\n";
echo "Percentage Variance: {$variance->getPercentageVariance()}%\n";
echo "Status: " . ($variance->isUnderBudget() ? "Under Budget ✓" : "Over Budget ✗") . "\n";
echo "Severity: {$variance->getSeverity()->name}\n";

// Expected output:
// Budget created: budget-01HJKM2X3Y4Z5A6B7C8D9E0F1G
// Allocated: 50000.00 MYR
// ✓ Budget available for 12500.00
// Available amount: 50000.00
// Budget committed: 12500.00 for PO-2025-0001
// Actual recorded: 12350.00 from JE-2025-0042
//
// === Budget Variance Analysis ===
// Absolute Variance: 37650.00
// Percentage Variance: 75.30%
// Status: Under Budget ✓
// Severity: Low
