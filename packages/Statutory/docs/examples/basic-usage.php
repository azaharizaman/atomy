<?php

declare(strict_types=1);

/**
 * Basic Usage Examples: Nexus Statutory
 * 
 * This file demonstrates basic report generation scenarios.
 */

use Nexus\Statutory\Services\StatutoryReportManager;
use Nexus\Statutory\Enums\ReportFormat;
use Nexus\Statutory\Enums\FilingFrequency;

// =============================================================================
// Example 1: Generate Profit & Loss Report (JSON Format)
// =============================================================================

/** @var StatutoryReportManager $reportManager */
$reportManager = app(StatutoryReportManager::class);

// Generate P&L report for 2024
$profitLossReport = $reportManager->generateReport(
    reportType: 'profit_loss',
    startDate: new \DateTimeImmutable('2024-01-01'),
    endDate: new \DateTimeImmutable('2024-12-31'),
    format: ReportFormat::JSON,
    options: [
        'include_comparative' => true, // Include prior year comparison
        'consolidate_subsidiaries' => false,
    ]
);

echo "Generated Report ID: {$profitLossReport->getId()}\n";
echo "Report Type: {$profitLossReport->getReportType()}\n";
echo "Format: {$profitLossReport->getFormat()}\n";
echo "Generated At: {$profitLossReport->getGeneratedAt()->format('Y-m-d H:i:s')}\n";

// Access report content
$content = $profitLossReport->getContent();
echo "Report Data:\n";
print_r($content);

// =============================================================================
// Example 2: Generate Balance Sheet Report (PDF Export)
// =============================================================================

$balanceSheetReport = $reportManager->generateReport(
    reportType: 'balance_sheet',
    startDate: new \DateTimeImmutable('2024-01-01'),
    endDate: new \DateTimeImmutable('2024-12-31'),
    format: ReportFormat::PDF,
    options: [
        'include_notes' => true, // Include financial statement notes
        'detailed_breakdown' => true,
    ]
);

// Download PDF
$pdfContent = $balanceSheetReport->getContent()['pdf'];
file_put_contents('/tmp/balance_sheet_2024.pdf', $pdfContent);
echo "PDF saved to: /tmp/balance_sheet_2024.pdf\n";

// =============================================================================
// Example 3: Generate CSV Export for Data Import
// =============================================================================

$csvReport = $reportManager->generateReport(
    reportType: 'trial_balance',
    startDate: new \DateTimeImmutable('2024-01-01'),
    endDate: new \DateTimeImmutable('2024-12-31'),
    format: ReportFormat::CSV,
    options: [
        'include_zero_balances' => false, // Exclude accounts with zero balance
        'group_by_category' => true,
    ]
);

// Access CSV content
$csvContent = $csvReport->getContent()['csv'];
file_put_contents('/tmp/trial_balance_2024.csv', $csvContent);
echo "CSV saved to: /tmp/trial_balance_2024.csv\n";

// =============================================================================
// Example 4: Validate Report Against Schema (XBRL)
// =============================================================================

// First, generate XBRL report
$xbrlReport = $reportManager->generateReport(
    reportType: 'financial_statement',
    startDate: new \DateTimeImmutable('2024-01-01'),
    endDate: new \DateTimeImmutable('2024-12-31'),
    format: ReportFormat::XBRL,
    options: [
        'schema_id' => 'MY-GAAP-2024',
        'schema_version' => '1.0',
    ]
);

// Validate the report
try {
    $validationResult = $reportManager->validateReport($xbrlReport->getId());
    echo "Validation Status: {$validationResult['status']}\n";
    
    if ($validationResult['status'] === 'valid') {
        echo "Report is valid and ready for submission!\n";
    } else {
        echo "Validation Errors:\n";
        print_r($validationResult['errors']);
    }
} catch (\Nexus\Statutory\Exceptions\ValidationException $e) {
    echo "Validation failed: {$e->getMessage()}\n";
    print_r($e->getValidationErrors());
}

// =============================================================================
// Example 5: Retrieve Existing Report
// =============================================================================

// Retrieve report by ID
$existingReport = $reportManager->getReport($profitLossReport->getId());

echo "Retrieved Report:\n";
echo "ID: {$existingReport->getId()}\n";
echo "Type: {$existingReport->getReportType()}\n";
echo "Status: {$existingReport->getStatus()}\n";

// Check if report has been submitted
if ($existingReport->getSubmittedAt()) {
    echo "Submitted At: {$existingReport->getSubmittedAt()->format('Y-m-d H:i:s')}\n";
} else {
    echo "Status: Not yet submitted\n";
}

// =============================================================================
// Example 6: Get Report Metadata (Schema Information)
// =============================================================================

use Nexus\Statutory\Contracts\ReportMetadataInterface;

/** @var ReportMetadataInterface $metadataProvider */
$metadataProvider = app(ReportMetadataInterface::class);

// Get metadata for SSM Form 9A (Malaysian Companies Act 2016)
$metadata = $metadataProvider->getMetadata('ssm_form_9a');

echo "Report Schema Information:\n";
echo "Schema ID: {$metadata['schema_id']}\n";
echo "Schema Version: {$metadata['schema_version']}\n";
echo "Schema URL: {$metadata['schema_url']}\n";
echo "Filing Frequency: {$metadata['filing_frequency']}\n";
echo "Authority: {$metadata['authority']}\n";

// Get validation rules
$validationRules = $metadataProvider->getValidationRules('ssm_form_9a');
echo "Validation Rules:\n";
print_r($validationRules);

// =============================================================================
// Example 7: Multi-Format Conversion
// =============================================================================

// Generate report in multiple formats from the same data
$formats = [ReportFormat::JSON, ReportFormat::PDF, ReportFormat::CSV, ReportFormat::XBRL];

foreach ($formats as $format) {
    $report = $reportManager->generateReport(
        reportType: 'cash_flow',
        startDate: new \DateTimeImmutable('2024-Q1-01'),
        endDate: new \DateTimeImmutable('2024-Q1-31'),
        format: $format,
        options: []
    );
    
    echo "Generated {$format->value} report: {$report->getId()}\n";
}

// =============================================================================
// Example 8: Using Default Payroll Statutory Adapter
// =============================================================================

use Nexus\Statutory\Contracts\PayrollStatutoryInterface;

/** @var PayrollStatutoryInterface $payrollStatutory */
$payrollStatutory = app(PayrollStatutoryInterface::class);

// Calculate payroll deductions (uses default adapter if no country-specific implementation)
$deductions = $payrollStatutory->calculateDeductions(
    grossSalary: 5000.00,
    deductionType: 'EPF', // Employee Provident Fund
    periodStart: new \DateTimeImmutable('2024-01-01'),
    periodEnd: new \DateTimeImmutable('2024-01-31'),
    additionalParams: [
        'employee_category' => 'full_time',
        'employee_age' => 30,
    ]
);

echo "Payroll Deductions:\n";
echo "Employee Share: {$deductions['employee_share']}\n";
echo "Employer Share: {$deductions['employer_share']}\n";
echo "Total Deduction: {$deductions['total']}\n";

// =============================================================================
// Example 9: Check Filing Frequency Requirements
// =============================================================================

// Determine if a report needs to be filed this period
$reportType = 'vat_return';
$filingFrequency = $metadataProvider->getFilingFrequency($reportType);

echo "Filing Frequency for {$reportType}: {$filingFrequency->value}\n";

// Check if filing is due
$lastFilingDate = new \DateTimeImmutable('2024-09-30');
$nextFilingDate = match ($filingFrequency) {
    FilingFrequency::MONTHLY => $lastFilingDate->modify('+1 month'),
    FilingFrequency::QUARTERLY => $lastFilingDate->modify('+3 months'),
    FilingFrequency::SEMI_ANNUALLY => $lastFilingDate->modify('+6 months'),
    FilingFrequency::ANNUALLY => $lastFilingDate->modify('+1 year'),
    FilingFrequency::BIENNIAL => $lastFilingDate->modify('+2 years'),
    FilingFrequency::ON_DEMAND => null, // No automatic due date
};

if ($nextFilingDate) {
    echo "Next Filing Due: {$nextFilingDate->format('Y-m-d')}\n";
    
    $daysUntilDue = (new \DateTime())->diff($nextFilingDate)->days;
    echo "Days Until Due: {$daysUntilDue}\n";
}

// =============================================================================
// Example 10: Error Handling Best Practices
// =============================================================================

use Nexus\Statutory\Exceptions\ReportNotFoundException;
use Nexus\Statutory\Exceptions\InvalidReportTypeException;
use Nexus\Statutory\Exceptions\DataExtractionException;

try {
    // Attempt to generate report with invalid type
    $reportManager->generateReport(
        reportType: 'invalid_report_type',
        startDate: new \DateTimeImmutable('2024-01-01'),
        endDate: new \DateTimeImmutable('2024-12-31'),
        format: ReportFormat::JSON,
        options: []
    );
} catch (InvalidReportTypeException $e) {
    echo "Error: {$e->getMessage()}\n";
    echo "Valid report types: " . implode(', ', $e->getValidReportTypes()) . "\n";
}

try {
    // Attempt to retrieve non-existent report
    $reportManager->getReport('non-existent-id');
} catch (ReportNotFoundException $e) {
    echo "Error: {$e->getMessage()}\n";
    echo "Report ID: {$e->getReportId()}\n";
}

try {
    // Attempt to generate report with missing GL data
    $reportManager->generateReport(
        reportType: 'profit_loss',
        startDate: new \DateTimeImmutable('2025-01-01'), // Future date with no data
        endDate: new \DateTimeImmutable('2025-12-31'),
        format: ReportFormat::JSON,
        options: []
    );
} catch (DataExtractionException $e) {
    echo "Error: {$e->getMessage()}\n";
    echo "Failed to extract GL data for future period\n";
}

// =============================================================================
// Summary
// =============================================================================

echo "\n";
echo "==============================================\n";
echo "Basic Usage Examples Completed\n";
echo "==============================================\n";
echo "Demonstrated:\n";
echo "1. Generating P&L report (JSON)\n";
echo "2. Generating Balance Sheet (PDF)\n";
echo "3. Generating Trial Balance (CSV)\n";
echo "4. XBRL validation\n";
echo "5. Retrieving existing reports\n";
echo "6. Report metadata and schema information\n";
echo "7. Multi-format conversion\n";
echo "8. Payroll statutory calculations\n";
echo "9. Filing frequency checks\n";
echo "10. Error handling patterns\n";
echo "==============================================\n";
