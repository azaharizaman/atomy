<?php

declare(strict_types=1);

/**
 * Advanced Usage Example: Accounting Package
 * 
 * Demonstrates:
 * 1. Multi-entity consolidation with eliminations
 * 2. Comparative period reporting
 * 3. Exporting statements to multiple formats
 */

use Nexus\Accounting\Contracts\AccountingManagerInterface;
use Nexus\Accounting\Core\ValueObjects\{ReportingPeriod, ConsolidationRule};
use Nexus\Accounting\Core\Enums\{ConsolidationMethod, StatementType};

// ============================================
// Example 1: Multi-Entity Consolidation
// ============================================

$parentId = '01JQKZ8PARENT123';
$subsidiaryIds = ['01JQKZ8SUB1', '01JQKZ8SUB2', '01JQKZ8SUB3'];
$period = ReportingPeriod::yearly(new \DateTimeImmutable('2024-12-31'));

// Configure elimination rules
$eliminationRules = [
    new ConsolidationRule(
        eliminationType: 'intercompany_revenue',
        criteriaJson: json_encode(['account_category' => 'IC_REVENUE']),
        description: 'Eliminate intercompany sales'
    ),
    new ConsolidationRule(
        eliminationType: 'intercompany_payable',
        criteriaJson: json_encode(['account_category' => 'IC_PAYABLE']),
        description: 'Eliminate intercompany balances'
    ),
];

$consolidated = $accountingManager->consolidateEntities(
    parentTenantId: $parentId,
    subsidiaryTenantIds: $subsidiaryIds,
    period: $period,
    method: ConsolidationMethod::Full,
    eliminationRules: $eliminationRules
);

echo "Consolidated Income Statement\n";
echo "Total Revenue (after eliminations): {$consolidated->getTotalRevenue()}\n";
echo "Net Income: {$consolidated->getNetIncome()}\n\n";

// ============================================
// Example 2: Comparative Period Reporting
// ============================================

$currentPeriod = ReportingPeriod::monthly(new \DateTimeImmutable('2024-11-30'));
$priorPeriod = ReportingPeriod::monthly(new \DateTimeImmutable('2023-11-30'));

$comparison = $accountingManager->comparePeriods(
    tenantId: $parentId,
    currentPeriod: $currentPeriod,
    priorPeriod: $priorPeriod,
    statementType: StatementType::IncomeStatement
);

foreach ($comparison->getLineItemComparisons() as $item) {
    echo sprintf(
        "%s: Current %s, Prior %s, Change %s%%\n",
        $item->getDescription(),
        $item->getCurrentAmount(),
        $item->getPriorAmount(),
        $item->getPercentageChange()
    );
}

// ============================================
// Example 3: Export to Multiple Formats
// ============================================

// Export to PDF
$pdfPath = $accountingManager->exportStatement(
    statementId: $balanceSheet->getId(),
    format: 'pdf',
    outputPath: '/tmp/balance-sheet.pdf'
);

// Export to Excel
$excelPath = $accountingManager->exportStatement(
    statementId: $balanceSheet->getId(),
    format: 'excel',
    outputPath: '/tmp/balance-sheet.xlsx'
);

echo "Exported to:\n";
echo "  PDF: {$pdfPath}\n";
echo "  Excel: {$excelPath}\n";
