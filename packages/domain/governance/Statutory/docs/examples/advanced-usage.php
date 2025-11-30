<?php

declare(strict_types=1);

/**
 * Advanced Usage Examples: Nexus Statutory
 * 
 * This file demonstrates advanced scenarios including:
 * - Country-specific adapter usage
 * - XBRL generation with taxonomy mapping
 * - Multi-format conversion with consistency checks
 * - GL-to-Taxonomy mapping management
 * - Event-driven architecture
 * - Complex validation scenarios
 */

use Nexus\Statutory\Services\StatutoryReportManager;
use Nexus\Statutory\Contracts\TaxonomyReportGeneratorInterface;
use Nexus\Statutory\Contracts\ReportMetadataInterface;
use Nexus\Statutory\Contracts\PayrollStatutoryInterface;
use Nexus\Statutory\Enums\ReportFormat;
use Nexus\Statutory\Enums\FilingFrequency;

// =============================================================================
// Example 1: Country-Specific Adapter (Malaysian SSM XBRL)
// =============================================================================

/**
 * Malaysian companies must submit financial statements to SSM (Companies Commission)
 * in XBRL format using MY-GAAP or MY-FRS taxonomy.
 */

/** @var TaxonomyReportGeneratorInterface $accountingAdapter */
$accountingAdapter = app(TaxonomyReportGeneratorInterface::class);

// Generate taxonomy-mapped data for SSM Form 9A submission
$taxonomyData = $accountingAdapter->generateReport(
    reportType: 'ssm_form_9a',
    startDate: new \DateTimeImmutable('2024-01-01'),
    endDate: new \DateTimeImmutable('2024-12-31'),
    options: [
        'schema_id' => 'MY-GAAP-2024',
        'schema_version' => '1.0',
        'company_number' => '202401234567',
        'fiscal_year_end' => '2024-12-31',
        'include_notes' => true,
        'comparative_period' => true, // Include 2023 comparative figures
    ]
);

echo "Taxonomy-Mapped Financial Data (SSM Form 9A):\n";
print_r($taxonomyData);

// Expected output structure:
/*
[
    'Revenue' => [
        'value' => 5000000,
        'account_name' => 'Sales Revenue',
        'account_code' => '4000',
        'taxonomy_code' => 'Revenue',
        'prior_year_value' => 4500000,
    ],
    'CostOfSales' => [
        'value' => 3000000,
        'account_name' => 'Cost of Goods Sold',
        'account_code' => '5000',
        'taxonomy_code' => 'CostOfSales',
        'prior_year_value' => 2700000,
    ],
    // ... more taxonomy elements
]
*/

// =============================================================================
// Example 2: XBRL Generation with Full Validation
// =============================================================================

/** @var StatutoryReportManager $reportManager */
$reportManager = app(StatutoryReportManager::class);

// Generate XBRL report with comprehensive validation
$xbrlReport = $reportManager->generateReport(
    reportType: 'financial_statement',
    startDate: new \DateTimeImmutable('2024-01-01'),
    endDate: new \DateTimeImmutable('2024-12-31'),
    format: ReportFormat::XBRL,
    options: [
        'schema_id' => 'MY-GAAP-2024',
        'schema_version' => '1.0',
        'validate_on_generation' => true, // Validate during generation
        'company_info' => [
            'name' => 'ACME Corporation Sdn Bhd',
            'registration_number' => '202401234567',
            'tax_identification' => 'C1234567890',
            'fiscal_year_end' => '2024-12-31',
        ],
        'report_metadata' => [
            'preparer_name' => 'John Doe',
            'preparer_designation' => 'Chief Financial Officer',
            'audit_firm' => 'ABC Audit Partners',
            'audit_opinion' => 'unqualified',
        ],
    ]
);

// Access XBRL content
$xbrlContent = $xbrlReport->getContent()['xbrl'];

// Validate against schema
try {
    $validationResult = $reportManager->validateReport($xbrlReport->getId());
    
    if ($validationResult['status'] === 'valid') {
        echo "✓ XBRL Report is valid!\n";
        echo "  Schema: {$validationResult['schema_id']}\n";
        echo "  Version: {$validationResult['schema_version']}\n";
        
        // Save for submission
        file_put_contents('/tmp/ssm_form_9a_2024.xbrl', $xbrlContent);
        echo "  Saved to: /tmp/ssm_form_9a_2024.xbrl\n";
    } else {
        echo "✗ XBRL Validation Failed:\n";
        foreach ($validationResult['errors'] as $error) {
            echo "  - {$error['element']}: {$error['message']}\n";
        }
    }
} catch (\Nexus\Statutory\Exceptions\ValidationException $e) {
    echo "Validation Error: {$e->getMessage()}\n";
    print_r($e->getValidationErrors());
}

// =============================================================================
// Example 3: Multi-Format Conversion with Consistency Checks
// =============================================================================

/**
 * Generate the same financial statement in multiple formats and verify
 * that the data is consistent across all formats.
 */

$reportId = '01JDPF8K3XBRMZ5QWVGJ9Y2N3E'; // Use consistent ID
$reportTypes = [
    ReportFormat::JSON,
    ReportFormat::XBRL,
    ReportFormat::PDF,
    ReportFormat::CSV,
];

$reports = [];

foreach ($reportTypes as $format) {
    $report = $reportManager->generateReport(
        reportType: 'balance_sheet',
        startDate: new \DateTimeImmutable('2024-01-01'),
        endDate: new \DateTimeImmutable('2024-12-31'),
        format: $format,
        options: [
            'report_id' => $reportId, // Use same report ID for all formats
            'schema_id' => 'MY-GAAP-2024',
        ]
    );
    
    $reports[$format->value] = $report;
    echo "Generated {$format->value} report: {$report->getId()}\n";
}

// Verify consistency: Total Assets should be the same across all formats
$totalAssetsJson = $reports['JSON']->getContent()['taxonomy_data']['TotalAssets']['value'];
$totalAssetsCsv = extractTotalAssetsFromCsv($reports['CSV']->getContent()['csv']);

if ($totalAssetsJson === $totalAssetsCsv) {
    echo "✓ Consistency check passed: Total Assets = {$totalAssetsJson}\n";
} else {
    echo "✗ Inconsistency detected!\n";
    echo "  JSON: {$totalAssetsJson}\n";
    echo "  CSV: {$totalAssetsCsv}\n";
}

// Helper function
function extractTotalAssetsFromCsv(string $csvContent): float
{
    $lines = explode("\n", $csvContent);
    foreach ($lines as $line) {
        if (str_contains($line, 'TotalAssets')) {
            $parts = str_getcsv($line);
            return (float) $parts[1];
        }
    }
    return 0.0;
}

// =============================================================================
// Example 4: GL-to-Taxonomy Mapping Management
// =============================================================================

/**
 * Create and manage GL account to taxonomy code mappings.
 * This is crucial for accurate XBRL generation.
 */

use Illuminate\Support\Facades\DB;

// Create taxonomy mappings for all revenue accounts
$revenueAccounts = [
    'GL-4000' => 'Revenue',
    'GL-4100' => 'OtherIncome',
    'GL-4200' => 'InterestIncome',
];

foreach ($revenueAccounts as $glAccountId => $taxonomyCode) {
    DB::table('taxonomy_mappings')->insert([
        'id' => \Illuminate\Support\Str::ulid(),
        'tenant_id' => app(\Nexus\Tenant\Contracts\TenantContextInterface::class)->getCurrentTenantId(),
        'gl_account_id' => $glAccountId,
        'taxonomy_code' => $taxonomyCode,
        'schema_id' => 'MY-GAAP-2024',
        'schema_version' => '1.0',
        'effective_from' => '2024-01-01',
        'effective_to' => null, // Open-ended
        'metadata' => json_encode([
            'mapped_by' => 'system',
            'mapping_confidence' => 'high',
            'notes' => 'Automated mapping based on account type',
        ]),
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    
    echo "Mapped {$glAccountId} → {$taxonomyCode}\n";
}

// Create version-specific mapping (when taxonomy version changes)
DB::table('taxonomy_mappings')->insert([
    'id' => \Illuminate\Support\Str::ulid(),
    'tenant_id' => app(\Nexus\Tenant\Contracts\TenantContextInterface::class)->getCurrentTenantId(),
    'gl_account_id' => 'GL-1000',
    'taxonomy_code' => 'CashAndCashEquivalents',
    'schema_id' => 'MY-GAAP-2024',
    'schema_version' => '1.1', // Updated schema version
    'effective_from' => '2024-07-01', // New mapping effective from July 2024
    'effective_to' => null,
    'metadata' => json_encode([
        'change_reason' => 'Schema version update (1.0 → 1.1)',
        'previous_taxonomy_code' => 'Cash',
    ]),
    'created_at' => now(),
    'updated_at' => now(),
]);

echo "Created version-specific mapping for GL-1000\n";

// Query mappings for a specific date (temporal query)
$mappingsAsOfDate = DB::table('taxonomy_mappings')
    ->where('tenant_id', app(\Nexus\Tenant\Contracts\TenantContextInterface::class)->getCurrentTenantId())
    ->where('effective_from', '<=', '2024-12-31')
    ->where(function ($query) {
        $query->whereNull('effective_to')
            ->orWhere('effective_to', '>=', '2024-12-31');
    })
    ->get();

echo "Active mappings as of 2024-12-31: {$mappingsAsOfDate->count()}\n";

// =============================================================================
// Example 5: Payroll Statutory Calculations (Country-Specific)
// =============================================================================

/**
 * Use country-specific payroll statutory adapter for accurate calculations.
 * Malaysia uses Nexus\PayrollMysStatutory package.
 */

/** @var PayrollStatutoryInterface $payrollStatutory */
$payrollStatutory = app(PayrollStatutoryInterface::class);

// Calculate EPF (Employee Provident Fund) deductions
$epfDeductions = $payrollStatutory->calculateDeductions(
    grossSalary: 8000.00,
    deductionType: 'EPF',
    periodStart: new \DateTimeImmutable('2024-01-01'),
    periodEnd: new \DateTimeImmutable('2024-01-31'),
    additionalParams: [
        'employee_age' => 35,
        'employee_category' => 'full_time',
        'employee_nationality' => 'malaysian',
    ]
);

echo "EPF Deductions for RM 8,000 gross salary:\n";
echo "  Employee Share (11%): RM {$epfDeductions['employee_share']}\n";
echo "  Employer Share (13%): RM {$epfDeductions['employer_share']}\n";
echo "  Total EPF: RM {$epfDeductions['total']}\n";

// Calculate SOCSO (Social Security Organization) deductions
$socsoDeductions = $payrollStatutory->calculateDeductions(
    grossSalary: 8000.00,
    deductionType: 'SOCSO',
    periodStart: new \DateTimeImmutable('2024-01-01'),
    periodEnd: new \DateTimeImmutable('2024-01-31'),
    additionalParams: [
        'employee_age' => 35,
        'employment_type' => 'full_time',
    ]
);

echo "SOCSO Deductions:\n";
echo "  Employee Share: RM {$socsoDeductions['employee_share']}\n";
echo "  Employer Share: RM {$socsoDeductions['employer_share']}\n";
echo "  Total SOCSO: RM {$socsoDeductions['total']}\n";

// Calculate PCB (Monthly Tax Deduction / Potongan Cukai Bulanan)
$pcbDeduction = $payrollStatutory->calculateDeductions(
    grossSalary: 8000.00,
    deductionType: 'PCB',
    periodStart: new \DateTimeImmutable('2024-01-01'),
    periodEnd: new \DateTimeImmutable('2024-01-31'),
    additionalParams: [
        'tax_status' => 'married',
        'number_of_children' => 2,
        'other_income' => 0.00,
    ]
);

echo "PCB (Monthly Tax Deduction):\n";
echo "  PCB Amount: RM {$pcbDeduction['employee_share']}\n";

// =============================================================================
// Example 6: Event-Driven Architecture (Report Lifecycle)
// =============================================================================

/**
 * Listen to report lifecycle events and trigger actions.
 */

use Illuminate\Support\Facades\Event;
use Nexus\Statutory\Events\ReportGeneratedEvent;
use Nexus\Statutory\Events\ReportValidatedEvent;
use Nexus\Statutory\Events\ReportSubmittedEvent;

// Listen for report generation
Event::listen(ReportGeneratedEvent::class, function (ReportGeneratedEvent $event) {
    $report = $event->getReport();
    
    // Send notification to accountant
    $notifier = app(\Nexus\Notifier\Contracts\NotificationManagerInterface::class);
    $notifier->send(
        recipient: 'accountant@company.com',
        channel: 'email',
        template: 'statutory.report_generated',
        data: [
            'report_id' => $report->getId(),
            'report_type' => $report->getReportType(),
            'format' => $report->getFormat(),
            'generated_at' => $report->getGeneratedAt()->format('Y-m-d H:i:s'),
        ]
    );
    
    // Log to audit trail
    $auditLogger = app(\Nexus\AuditLogger\Contracts\AuditLogManagerInterface::class);
    $auditLogger->log(
        entityId: $report->getId(),
        action: 'report_generated',
        description: "Statutory report {$report->getReportType()} generated in {$report->getFormat()} format",
        metadata: [
            'report_type' => $report->getReportType(),
            'format' => $report->getFormat(),
        ]
    );
    
    echo "Event: Report {$report->getId()} generated - notifications sent\n";
});

// Listen for validation completion
Event::listen(ReportValidatedEvent::class, function (ReportValidatedEvent $event) {
    $report = $event->getReport();
    $validationResult = $event->getValidationResult();
    
    if ($validationResult['status'] === 'valid') {
        echo "Event: Report {$report->getId()} validated successfully\n";
        
        // Automatically submit to authority (if configured)
        // $reportManager->submitToAuthority($report->getId());
    } else {
        echo "Event: Report {$report->getId()} validation failed\n";
        
        // Send alert to CFO
        $notifier = app(\Nexus\Notifier\Contracts\NotificationManagerInterface::class);
        $notifier->send(
            recipient: 'cfo@company.com',
            channel: 'email',
            template: 'statutory.validation_failed',
            data: [
                'report_id' => $report->getId(),
                'errors' => $validationResult['errors'],
            ]
        );
    }
});

// Generate report to trigger events
$eventDemoReport = $reportManager->generateReport(
    reportType: 'profit_loss',
    startDate: new \DateTimeImmutable('2024-01-01'),
    endDate: new \DateTimeImmutable('2024-12-31'),
    format: ReportFormat::JSON,
    options: []
);

// =============================================================================
// Example 7: Complex Validation Scenarios
// =============================================================================

/**
 * Demonstrate handling of complex validation errors.
 */

try {
    // Generate report with missing mandatory taxonomy elements
    $invalidReport = $reportManager->generateReport(
        reportType: 'cash_flow',
        startDate: new \DateTimeImmutable('2024-01-01'),
        endDate: new \DateTimeImmutable('2024-12-31'),
        format: ReportFormat::XBRL,
        options: [
            'schema_id' => 'MY-GAAP-2024',
            'validate_on_generation' => true,
            // Missing required company info
        ]
    );
} catch (\Nexus\Statutory\Exceptions\ValidationException $e) {
    echo "Validation Exception Caught:\n";
    echo "Message: {$e->getMessage()}\n";
    
    $errors = $e->getValidationErrors();
    foreach ($errors as $field => $errorMessages) {
        echo "Field: {$field}\n";
        foreach ($errorMessages as $error) {
            echo "  - {$error}\n";
        }
    }
    
    // Example error handling:
    // 1. Log error
    \Illuminate\Support\Facades\Log::error('XBRL validation failed', [
        'report_type' => 'cash_flow',
        'errors' => $errors,
    ]);
    
    // 2. Notify user
    echo "Please correct the following errors and try again.\n";
}

// =============================================================================
// Example 8: Batch Report Generation (Year-End Processing)
// =============================================================================

/**
 * Generate all required year-end statutory reports in batch.
 */

$yearEndReports = [
    'profit_loss' => 'Profit & Loss Statement',
    'balance_sheet' => 'Balance Sheet',
    'cash_flow' => 'Cash Flow Statement',
    'changes_in_equity' => 'Statement of Changes in Equity',
];

$yearEndDate = new \DateTimeImmutable('2024-12-31');
$yearStartDate = new \DateTimeImmutable('2024-01-01');

echo "Generating Year-End Reports for 2024:\n";

foreach ($yearEndReports as $reportType => $reportName) {
    try {
        $report = $reportManager->generateReport(
            reportType: $reportType,
            startDate: $yearStartDate,
            endDate: $yearEndDate,
            format: ReportFormat::XBRL,
            options: [
                'schema_id' => 'MY-GAAP-2024',
                'validate_on_generation' => true,
            ]
        );
        
        echo "  ✓ {$reportName}: {$report->getId()}\n";
        
    } catch (\Throwable $e) {
        echo "  ✗ {$reportName}: {$e->getMessage()}\n";
    }
}

// =============================================================================
// Example 9: Custom Report Metadata Provider
// =============================================================================

/**
 * Implement custom metadata provider for organization-specific requirements.
 */

use Nexus\Statutory\Contracts\ReportMetadataInterface;

class CustomReportMetadata implements ReportMetadataInterface
{
    public function getMetadata(string $reportType): array
    {
        // Custom metadata logic
        return [
            'schema_id' => 'MY-GAAP-2024',
            'schema_version' => '1.0',
            'schema_url' => 'https://taxonomy.ssm.gov.my/gaap/2024/schema.xsd',
            'namespace' => 'http://taxonomy.ssm.gov.my/gaap/2024',
            'filing_frequency' => FilingFrequency::ANNUALLY->value,
            'submission_authority' => 'SSM (Companies Commission of Malaysia)',
            'custom_field' => 'organization-specific-value',
        ];
    }

    public function getSchemaId(string $reportType): string
    {
        return 'MY-GAAP-2024';
    }

    public function getSchemaVersion(string $reportType): string
    {
        return '1.0';
    }

    public function getSchemaUrl(string $reportType): string
    {
        return 'https://taxonomy.ssm.gov.my/gaap/2024/schema.xsd';
    }

    public function getFilingFrequency(string $reportType): FilingFrequency
    {
        return FilingFrequency::ANNUALLY;
    }

    public function getSubmissionAuthority(string $reportType): string
    {
        return 'SSM (Companies Commission of Malaysia)';
    }

    public function getValidationRules(string $reportType): array
    {
        return [
            'company_number' => 'required|regex:/^\d{12}$/',
            'fiscal_year_end' => 'required|date',
            'revenue' => 'required|numeric|min:0',
        ];
    }

    public function getRequiredFields(string $reportType): array
    {
        return ['company_number', 'fiscal_year_end', 'revenue'];
    }

    public function getOptionalFields(string $reportType): array
    {
        return ['audit_firm', 'preparer_name'];
    }

    public function getTaxonomyNamespace(string $reportType): string
    {
        return 'http://taxonomy.ssm.gov.my/gaap/2024';
    }
}

// Bind custom metadata provider in service provider
// app()->singleton(ReportMetadataInterface::class, CustomReportMetadata::class);

// =============================================================================
// Summary
// =============================================================================

echo "\n";
echo "==============================================\n";
echo "Advanced Usage Examples Completed\n";
echo "==============================================\n";
echo "Demonstrated:\n";
echo "1. Country-specific adapter (Malaysian SSM XBRL)\n";
echo "2. XBRL generation with full validation\n";
echo "3. Multi-format conversion with consistency checks\n";
echo "4. GL-to-Taxonomy mapping management\n";
echo "5. Payroll statutory calculations (EPF, SOCSO, PCB)\n";
echo "6. Event-driven architecture (report lifecycle)\n";
echo "7. Complex validation error handling\n";
echo "8. Batch year-end report generation\n";
echo "9. Custom report metadata provider\n";
echo "==============================================\n";
