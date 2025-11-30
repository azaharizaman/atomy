<?php

declare(strict_types=1);

/**
 * Advanced Usage Example: Payroll
 *
 * This example demonstrates advanced payroll scenarios:
 * 1. Multi-country statutory calculations
 * 2. Complex component formulas
 * 3. Retroactive payroll adjustments
 * 4. Bulk payroll processing with progress tracking
 * 5. GL journal posting integration
 * 6. Payslip PDF generation flow
 * 7. Year-end processing (Form EA/CP8D generation)
 */

use Nexus\Payroll\Services\PayrollEngine;
use Nexus\Payroll\Services\ComponentManager;
use Nexus\Payroll\Services\PayslipManager;
use Nexus\Payroll\Contracts\StatutoryCalculatorInterface;
use Nexus\Payroll\ValueObjects\ComponentType;
use Nexus\Payroll\ValueObjects\CalculationMethod;
use Nexus\Payroll\ValueObjects\PayslipStatus;
use Nexus\Finance\Contracts\GeneralLedgerManagerInterface;
use Nexus\AuditLogger\Contracts\AuditLogManagerInterface;
use Nexus\Notifier\Contracts\NotificationManagerInterface;

// ============================================
// Scenario 1: Multi-Country Statutory Setup
// ============================================

/**
 * Configure different statutory calculators for different countries.
 * This is typically done in the service provider.
 */

final readonly class MultiCountryPayrollService
{
    /**
     * @var array<string, StatutoryCalculatorInterface>
     */
    private array $calculators;

    public function __construct(
        private PayrollEngine $payrollEngine,
        private PayslipManager $payslipManager,
        StatutoryCalculatorInterface $malaysiaCalculator,
        StatutoryCalculatorInterface $singaporeCalculator,
    ) {
        $this->calculators = [
            'MY' => $malaysiaCalculator,
            'SG' => $singaporeCalculator,
        ];
    }

    public function processForCountry(
        string $tenantId,
        string $countryCode,
        string $periodStart,
        string $periodEnd
    ): array {
        $calculator = $this->calculators[$countryCode]
            ?? throw new \RuntimeException("No calculator for country: {$countryCode}");

        // Country-specific processing
        return $this->payrollEngine->processPeriodWithCalculator(
            $tenantId,
            $periodStart,
            $periodEnd,
            $calculator
        );
    }
}

echo "Scenario 1: Multi-Country Setup - Ready\n";

// ============================================
// Scenario 2: Complex Component Formulas
// ============================================

/**
 * Set up components with formula-based calculations.
 * Formulas support variables: BASIC, GROSS, DAYS_WORKED, HOURS_WORKED
 */

$componentManager = app(ComponentManager::class);

// Overtime - Formula based on hourly rate
$overtime = $componentManager->createComponent([
    'tenant_id' => 'tenant-001',
    'code' => 'OT',
    'name' => 'Overtime Pay',
    'type' => ComponentType::EARNING,
    'calculation_method' => CalculationMethod::FORMULA,
    'formula' => '(BASIC / 26 / 8) * 1.5 * OT_HOURS',
    'is_taxable' => true,
    'is_statutory' => true,
    'is_active' => true,
]);

echo "Created formula component: {$overtime->getCode()}\n";

// Performance Bonus - Percentage of basic with cap
$bonus = $componentManager->createComponent([
    'tenant_id' => 'tenant-001',
    'code' => 'PERF_BONUS',
    'name' => 'Performance Bonus',
    'type' => ComponentType::EARNING,
    'calculation_method' => CalculationMethod::FORMULA,
    'formula' => 'MIN(BASIC * PERF_RATING / 100, 5000)',
    'is_taxable' => true,
    'is_statutory' => false,
    'is_active' => true,
]);

echo "Created formula component: {$bonus->getCode()}\n";

// ============================================
// Scenario 3: Retroactive Payroll Adjustments
// ============================================

/**
 * Handle salary adjustments that need to be applied retroactively.
 * Example: Employee's salary was increased effective from 3 months ago.
 */

final readonly class RetroactiveAdjustmentService
{
    public function __construct(
        private PayrollEngine $payrollEngine,
        private PayslipManager $payslipManager,
        private AuditLogManagerInterface $auditLogger
    ) {}

    public function processRetroactiveAdjustment(
        string $tenantId,
        string $employeeId,
        string $effectiveFrom,
        array $componentAdjustments
    ): array {
        $adjustmentPayslips = [];

        // Get all periods from effective date to now
        $periods = $this->calculateAffectedPeriods($effectiveFrom);

        foreach ($periods as $period) {
            // Calculate difference for this period
            $originalPayslip = $this->payslipManager->getPayslipForPeriod(
                $tenantId,
                $employeeId,
                $period['start'],
                $period['end']
            );

            if ($originalPayslip === null) {
                continue;
            }

            // Calculate what the payslip should have been
            $recalculatedGross = $this->recalculateGross(
                $originalPayslip,
                $componentAdjustments
            );

            $difference = $recalculatedGross - $originalPayslip->getGrossPay();

            if (abs($difference) > 0.01) {
                // Create adjustment payslip
                $adjustmentPayslip = $this->payrollEngine->createAdjustmentPayslip(
                    $tenantId,
                    $employeeId,
                    $period['start'],
                    $period['end'],
                    [
                        [
                            'component_code' => 'RETRO_ADJ',
                            'amount' => $difference,
                            'description' => "Retroactive adjustment for {$period['start']} to {$period['end']}",
                        ],
                    ]
                );

                $adjustmentPayslips[] = $adjustmentPayslip;

                // Audit log
                $this->auditLogger->log(
                    entityId: $adjustmentPayslip->getId(),
                    action: 'retroactive_adjustment',
                    description: sprintf(
                        'Created retroactive adjustment of %.2f for period %s to %s',
                        $difference,
                        $period['start'],
                        $period['end']
                    ),
                    metadata: [
                        'original_gross' => $originalPayslip->getGrossPay(),
                        'new_gross' => $recalculatedGross,
                        'difference' => $difference,
                    ]
                );
            }
        }

        return $adjustmentPayslips;
    }

    private function calculateAffectedPeriods(string $effectiveFrom): array
    {
        // Returns array of period ranges from effective date to now
        // Implementation depends on your fiscal period setup
        return [];
    }

    private function recalculateGross(
        object $originalPayslip,
        array $componentAdjustments
    ): float {
        // Recalculate based on adjustments
        return 0.0;
    }
}

echo "Scenario 3: Retroactive Adjustments - Ready\n";

// ============================================
// Scenario 4: Bulk Processing with Progress
// ============================================

/**
 * Process large payrolls with progress tracking and error handling.
 * Suitable for organizations with 1000+ employees.
 */

final readonly class BulkPayrollProcessor
{
    public function __construct(
        private PayrollEngine $payrollEngine,
        private NotificationManagerInterface $notifier
    ) {}

    /**
     * @param callable $progressCallback fn(int $processed, int $total, ?string $error)
     */
    public function processBulkPayroll(
        string $tenantId,
        string $periodStart,
        string $periodEnd,
        array $employeeIds,
        callable $progressCallback
    ): array {
        $total = count($employeeIds);
        $processed = 0;
        $successful = [];
        $failed = [];

        // Process in chunks to manage memory
        $chunks = array_chunk($employeeIds, 50);

        foreach ($chunks as $chunk) {
            foreach ($chunk as $employeeId) {
                try {
                    $payslip = $this->payrollEngine->processEmployee(
                        $tenantId,
                        $employeeId,
                        $periodStart,
                        $periodEnd
                    );

                    $successful[] = [
                        'employee_id' => $employeeId,
                        'payslip_id' => $payslip->getId(),
                        'net_pay' => $payslip->getNetPay(),
                    ];

                    $progressCallback(++$processed, $total, null);
                } catch (\Throwable $e) {
                    $failed[] = [
                        'employee_id' => $employeeId,
                        'error' => $e->getMessage(),
                    ];

                    $progressCallback(++$processed, $total, $e->getMessage());
                }
            }

            // Free memory between chunks
            gc_collect_cycles();
        }

        // Send summary notification
        $this->notifier->send(
            recipient: 'hr@company.com',
            channel: 'email',
            template: 'payroll.bulk_processing_complete',
            data: [
                'period' => "{$periodStart} to {$periodEnd}",
                'total_employees' => $total,
                'successful' => count($successful),
                'failed' => count($failed),
                'total_net_pay' => array_sum(array_column($successful, 'net_pay')),
            ]
        );

        return [
            'successful' => $successful,
            'failed' => $failed,
        ];
    }
}

// Usage example
echo "Scenario 4: Bulk Processing\n";
$processor = app(BulkPayrollProcessor::class);

// Simulated usage (would be called with real data)
/*
$result = $processor->processBulkPayroll(
    tenantId: 'tenant-001',
    periodStart: '2025-01-01',
    periodEnd: '2025-01-31',
    employeeIds: $allEmployeeIds,
    progressCallback: function (int $processed, int $total, ?string $error) {
        echo "\rProcessing: {$processed}/{$total}";
        if ($error) {
            echo " - Error: {$error}";
        }
    }
);

echo "\n\nProcessing complete:\n";
echo "Successful: " . count($result['successful']) . "\n";
echo "Failed: " . count($result['failed']) . "\n";
*/

// ============================================
// Scenario 5: GL Journal Posting Integration
// ============================================

/**
 * Post payroll to General Ledger with proper account mapping.
 */

final readonly class PayrollGLIntegration
{
    public function __construct(
        private PayslipManager $payslipManager,
        private GeneralLedgerManagerInterface $glManager,
        private AuditLogManagerInterface $auditLogger
    ) {}

    /**
     * Post payroll for a period to GL
     */
    public function postToGL(
        string $tenantId,
        string $periodStart,
        string $periodEnd,
        array $accountMapping
    ): string {
        // Get all approved payslips for the period
        $payslips = $this->payslipManager->getPayslipsForPeriod(
            $tenantId,
            $periodStart,
            $periodEnd,
            [PayslipStatus::APPROVED]
        );

        if (empty($payslips)) {
            throw new \RuntimeException('No approved payslips to post');
        }

        // Aggregate amounts by component
        $aggregated = $this->aggregateByComponent($payslips);

        // Build journal entries
        $entries = [];

        // Expense entries (Debit)
        foreach ($aggregated['earnings'] as $code => $amount) {
            $entries[] = [
                'account_code' => $accountMapping['expenses'][$code] ?? '5100',
                'debit' => $amount,
                'credit' => 0,
                'description' => "Salary expense - {$code}",
            ];
        }

        // Employer contributions (Debit)
        foreach ($aggregated['employer_contributions'] as $code => $amount) {
            $entries[] = [
                'account_code' => $accountMapping['employer_contributions'][$code] ?? '5200',
                'debit' => $amount,
                'credit' => 0,
                'description' => "Employer contribution - {$code}",
            ];
        }

        // Statutory payables (Credit)
        foreach ($aggregated['statutory'] as $code => $amount) {
            $entries[] = [
                'account_code' => $accountMapping['statutory'][$code] ?? '2200',
                'debit' => 0,
                'credit' => $amount,
                'description' => "Statutory payable - {$code}",
            ];
        }

        // Net salary payable (Credit)
        $entries[] = [
            'account_code' => $accountMapping['salary_payable'] ?? '2100',
            'debit' => 0,
            'credit' => $aggregated['total_net_pay'],
            'description' => 'Net salary payable',
        ];

        // Post to GL
        $journalEntryId = $this->glManager->postJournalEntry([
            'entries' => $entries,
            'description' => "Payroll for period {$periodStart} to {$periodEnd}",
            'reference' => "PAYROLL-{$periodStart}",
            'date' => $periodEnd,
        ]);

        // Update payslips with GL reference
        foreach ($payslips as $payslip) {
            $this->payslipManager->setGLReference(
                $payslip->getId(),
                $journalEntryId
            );
        }

        // Audit log
        $this->auditLogger->log(
            entityId: $journalEntryId,
            action: 'payroll_posted_to_gl',
            description: sprintf(
                'Posted payroll for %d employees, total net pay: %.2f',
                count($payslips),
                $aggregated['total_net_pay']
            ),
            metadata: [
                'payslip_count' => count($payslips),
                'total_gross' => $aggregated['total_gross'],
                'total_deductions' => $aggregated['total_deductions'],
                'total_net_pay' => $aggregated['total_net_pay'],
            ]
        );

        return $journalEntryId;
    }

    private function aggregateByComponent(array $payslips): array
    {
        $result = [
            'earnings' => [],
            'deductions' => [],
            'statutory' => [],
            'employer_contributions' => [],
            'total_gross' => 0,
            'total_deductions' => 0,
            'total_net_pay' => 0,
        ];

        foreach ($payslips as $payslip) {
            foreach ($payslip->getEarningsBreakdown() as $code => $amount) {
                $result['earnings'][$code] = ($result['earnings'][$code] ?? 0) + $amount;
            }

            foreach ($payslip->getDeductionsBreakdown() as $code => $amount) {
                if (str_starts_with($code, 'EPF_') || str_starts_with($code, 'SOCSO_') || str_starts_with($code, 'EIS_') || $code === 'PCB') {
                    $result['statutory'][$code] = ($result['statutory'][$code] ?? 0) + $amount;
                } else {
                    $result['deductions'][$code] = ($result['deductions'][$code] ?? 0) + $amount;
                }
            }

            $result['total_gross'] += $payslip->getGrossPay();
            $result['total_deductions'] += $payslip->getTotalDeductions();
            $result['total_net_pay'] += $payslip->getNetPay();
        }

        return $result;
    }
}

echo "Scenario 5: GL Integration - Ready\n";

// ============================================
// Scenario 6: Payslip PDF Generation Flow
// ============================================

/**
 * Generate and distribute payslip PDFs.
 * Uses Nexus\Export for PDF generation and Nexus\Notifier for distribution.
 */

final readonly class PayslipDistributionService
{
    public function __construct(
        private PayslipManager $payslipManager,
        private NotificationManagerInterface $notifier,
        // private ExportManagerInterface $exporter, // From Nexus\Export
        // private StorageInterface $storage, // From Nexus\Storage
    ) {}

    public function generateAndDistribute(
        string $tenantId,
        string $periodStart,
        string $periodEnd
    ): array {
        $payslips = $this->payslipManager->getPayslipsForPeriod(
            $tenantId,
            $periodStart,
            $periodEnd,
            [PayslipStatus::APPROVED, PayslipStatus::PAID]
        );

        $distributed = [];

        foreach ($payslips as $payslip) {
            // Generate PDF
            $pdfPath = $this->generatePayslipPdf($payslip);

            // Store securely
            // $storedPath = $this->storage->store(
            //     path: "payslips/{$tenantId}/{$payslip->getEmployeeId()}/{$payslip->getPayslipNumber()}.pdf",
            //     contents: file_get_contents($pdfPath)
            // );

            // Send notification with PDF link
            $this->notifier->send(
                recipient: $payslip->getEmployeeId(),
                channel: 'email',
                template: 'payroll.payslip_available',
                data: [
                    'employee_name' => 'Employee', // Would come from Nexus\Party
                    'period' => "{$periodStart} to {$periodEnd}",
                    'net_pay' => number_format($payslip->getNetPay(), 2),
                    'payslip_url' => "https://app.example.com/payslips/{$payslip->getId()}",
                ]
            );

            $distributed[] = $payslip->getId();
        }

        return $distributed;
    }

    private function generatePayslipPdf(object $payslip): string
    {
        // Use Nexus\Export to generate PDF
        // return $this->exporter->export(
        //     template: 'payslip',
        //     data: $payslip->toArray(),
        //     format: 'pdf'
        // );
        return '/tmp/payslip.pdf';
    }
}

echo "Scenario 6: Payslip Distribution - Ready\n";

// ============================================
// Scenario 7: Year-End Processing (Malaysia)
// ============================================

/**
 * Generate year-end statutory forms for Malaysia.
 * - Form EA (employee's yearly income statement)
 * - CP8D (employer's declaration to LHDN)
 */

final readonly class YearEndProcessor
{
    public function __construct(
        private PayslipManager $payslipManager,
        // private StatutoryReportGeneratorInterface $reportGenerator,
        private AuditLogManagerInterface $auditLogger
    ) {}

    /**
     * Generate Form EA for all employees
     */
    public function generateFormEA(string $tenantId, int $year): array {
        $forms = [];

        // Get all employees with payslips for the year
        $yearStart = "{$year}-01-01";
        $yearEnd = "{$year}-12-31";

        $payslips = $this->payslipManager->getPayslipsForPeriod(
            $tenantId,
            $yearStart,
            $yearEnd,
            [PayslipStatus::PAID]
        );

        // Group by employee
        $employeePayslips = [];
        foreach ($payslips as $payslip) {
            $employeeId = $payslip->getEmployeeId();
            $employeePayslips[$employeeId][] = $payslip;
        }

        foreach ($employeePayslips as $employeeId => $payslipList) {
            $formData = $this->calculateFormEAData($payslipList, $year);

            $forms[$employeeId] = [
                'employee_id' => $employeeId,
                'year' => $year,
                'gross_income' => $formData['gross_income'],
                'epf_employee' => $formData['epf_employee'],
                'socso_employee' => $formData['socso_employee'],
                'eis_employee' => $formData['eis_employee'],
                'pcb_deducted' => $formData['pcb_deducted'],
                'benefits_in_kind' => $formData['bik'] ?? 0,
                'vola' => $formData['vola'] ?? 0, // Value of Living Accommodation
            ];
        }

        $this->auditLogger->log(
            entityId: "FORM_EA_{$tenantId}_{$year}",
            action: 'form_ea_generated',
            description: sprintf(
                'Generated Form EA for %d employees for year %d',
                count($forms),
                $year
            )
        );

        return $forms;
    }

    /**
     * Generate CP8D employer declaration
     */
    public function generateCP8D(string $tenantId, int $year): array
    {
        $formEAData = $this->generateFormEA($tenantId, $year);

        $cp8dData = [
            'year' => $year,
            'employer_tin' => '', // From tenant settings
            'employer_name' => '',
            'employees' => [],
            'totals' => [
                'total_employees' => count($formEAData),
                'total_gross_income' => 0,
                'total_epf' => 0,
                'total_pcb' => 0,
            ],
        ];

        foreach ($formEAData as $employeeId => $formData) {
            $cp8dData['employees'][] = [
                'employee_id' => $employeeId,
                // 'ic_number' => '', // From employee record
                // 'name' => '',
                'gross_income' => $formData['gross_income'],
                'epf' => $formData['epf_employee'],
                'pcb' => $formData['pcb_deducted'],
            ];

            $cp8dData['totals']['total_gross_income'] += $formData['gross_income'];
            $cp8dData['totals']['total_epf'] += $formData['epf_employee'];
            $cp8dData['totals']['total_pcb'] += $formData['pcb_deducted'];
        }

        $this->auditLogger->log(
            entityId: "CP8D_{$tenantId}_{$year}",
            action: 'cp8d_generated',
            description: sprintf(
                'Generated CP8D for year %d: %d employees, total gross RM %.2f',
                $year,
                $cp8dData['totals']['total_employees'],
                $cp8dData['totals']['total_gross_income']
            )
        );

        return $cp8dData;
    }

    private function calculateFormEAData(array $payslips, int $year): array
    {
        $data = [
            'gross_income' => 0,
            'epf_employee' => 0,
            'socso_employee' => 0,
            'eis_employee' => 0,
            'pcb_deducted' => 0,
        ];

        foreach ($payslips as $payslip) {
            $data['gross_income'] += $payslip->getGrossPay();

            $deductions = $payslip->getDeductionsBreakdown();
            $data['epf_employee'] += $deductions['EPF_EMPLOYEE'] ?? 0;
            $data['socso_employee'] += $deductions['SOCSO_EMPLOYEE'] ?? 0;
            $data['eis_employee'] += $deductions['EIS_EMPLOYEE'] ?? 0;
            $data['pcb_deducted'] += $deductions['PCB'] ?? 0;
        }

        return $data;
    }
}

echo "Scenario 7: Year-End Processing - Ready\n";

// ============================================
// Summary
// ============================================

echo "\n========================================\n";
echo "Advanced Payroll Examples Loaded\n";
echo "========================================\n";
echo "Scenarios demonstrated:\n";
echo "1. Multi-country statutory calculations\n";
echo "2. Complex component formulas\n";
echo "3. Retroactive payroll adjustments\n";
echo "4. Bulk processing with progress tracking\n";
echo "5. GL journal posting integration\n";
echo "6. Payslip PDF generation and distribution\n";
echo "7. Year-end statutory form generation\n";
echo "========================================\n";
