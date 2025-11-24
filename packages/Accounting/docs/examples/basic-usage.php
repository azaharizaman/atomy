<?php

declare(strict_types=1);

/**
 * Basic Usage Example: Accounting Package
 * 
 * Demonstrates:
 * 1. Generating financial statements (Balance Sheet, Income Statement, Cash Flow)
 * 2. Closing fiscal periods
 * 3. Calculating budget variance
 */

use Nexus\Accounting\Contracts\AccountingManagerInterface;
use Nexus\Accounting\Core\ValueObjects\ReportingPeriod;
use Nexus\Accounting\Core\ValueObjects\ComplianceStandard;

// ============================================
// Example 1: Generate Balance Sheet
// ============================================

$tenantId = '01JQKZ8EXAMPLE123';
$period = ReportingPeriod::monthly(new \DateTimeImmutable('2024-11-30'));

$balanceSheet = $accountingManager->generateBalanceSheet(
    tenantId: $tenantId,
    period: $period,
    standard: ComplianceStandard::mfrsMalaysia()
);

echo "Balance Sheet as of {$period->getEndDate()->format('Y-m-d')}\n";
echo "Total Assets: {$balanceSheet->getTotalAssets()}\n";
echo "Total Liabilities: {$balanceSheet->getTotalLiabilities()}\n";
echo "Total Equity: {$balanceSheet->getTotalEquity()}\n";
echo "Balanced: " . ($balanceSheet->isBalanced() ? 'Yes' : 'No') . "\n\n";

// ============================================
// Example 2: Close Period
// ============================================

$periodEnd = new \DateTimeImmutable('2024-11-30');
$closeResult = $accountingManager->closePeriod(
    tenantId: $tenantId,
    periodEnd: $periodEnd,
    closedBy: '01JQKZ8USER12345'
);

if ($closeResult->isSuccess()) {
    echo "✓ Period closed successfully\n";
} else {
    echo "✗ Period close failed\n";
    foreach ($closeResult->getValidationErrors() as $error) {
        echo "  - {$error}\n";
    }
}

// ============================================
// Example 3: Calculate Budget Variance
// ============================================

$variance = $accountingManager->calculateBudgetVariance(
    tenantId: $tenantId,
    period: ReportingPeriod::quarterly(new \DateTimeImmutable('2024-09-30'))
);

foreach ($variance->getVariancesByAccount() as $accountId => $accountVariance) {
    $direction = $accountVariance->isFavorable() ? '✓ Favorable' : '✗ Unfavorable';
    echo sprintf(
        "%s: %s variance of %s (%s%%)\n",
        $accountId,
        $direction,
        $accountVariance->getVarianceAmount(),
        $accountVariance->getVariancePercentage()
    );
}
