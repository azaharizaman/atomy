<?php

declare(strict_types=1);

namespace Nexus\HumanResourceOperations\Coordinators;

use Nexus\HumanResourceOperations\DataProviders\PayrollDataProvider;
use Nexus\HumanResourceOperations\DTOs\PayrollCalculationRequest;
use Nexus\HumanResourceOperations\DTOs\PayrollCalculationResult;
use Nexus\HumanResourceOperations\Services\PayrollCalculationService;
use Nexus\HumanResourceOperations\Services\PayrollRuleRegistry;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Coordinates payroll calculation with validation
 * 
 * Following Advanced Orchestrator Pattern:
 * - Coordinators are Traffic Cops (orchestrate flow)
 * - DataProviders aggregate data
 * - Services perform calculations
 * - Rules validate results
 */
final readonly class PayrollCoordinator
{
    public function __construct(
        private PayrollDataProvider $dataProvider,
        private PayrollCalculationService $calculationService,
        private PayrollRuleRegistry $ruleRegistry,
        // TODO: Inject PayrollManager from Nexus\Payroll package
        // private PayrollManagerInterface $payrollManager,
        private LoggerInterface $logger = new NullLogger()
    ) {}

    /**
     * Calculate payroll for employee with validation
     */
    public function calculatePayroll(PayrollCalculationRequest $request): PayrollCalculationResult
    {
        $this->logger->info('Calculating payroll', [
            'employee_id' => $request->employeeId,
            'period_id' => $request->periodId,
            'period_start' => $request->periodStart->format('Y-m-d'),
            'period_end' => $request->periodEnd->format('Y-m-d')
        ]);

        // Step 1: Aggregate data (Traffic Cop delegates to Data Provider)
        $context = $this->dataProvider->getPayrollContext(
            employeeId: $request->employeeId,
            periodId: $request->periodId,
            periodStart: $request->periodStart,
            periodEnd: $request->periodEnd
        );

        // Step 2: Validate context (Traffic Cop delegates to Rule Registry)
        $validationResults = $this->ruleRegistry->validate($context);
        $validationMessages = $this->ruleRegistry->getValidationMessages($validationResults);

        // Step 3: Calculate gross and net pay (Traffic Cop delegates to Service)
        $grossPay = $this->calculationService->calculateGrossPay($context);
        $netPay = $this->calculationService->calculateNetPay($context);

        // Step 4: Generate payslip (Traffic Cop delegates to Service)
        // TODO: Call Nexus\Payroll PayrollManager to generate and persist payslip
        $payslipId = $this->generatePayslip($request, $grossPay, $netPay, $context->earnings, $context->deductions);

        $this->logger->info('Payroll calculated', [
            'payslip_id' => $payslipId,
            'gross_pay' => $grossPay->toArray(),
            'net_pay' => $netPay->toArray(),
            'has_warnings' => !empty($validationMessages)
        ]);

        return new PayrollCalculationResult(
            success: true,
            payslipId: $payslipId,
            grossPay: $grossPay,
            netPay: $netPay,
            earnings: $context->earnings,
            deductions: $context->deductions,
            validationMessages: $validationMessages,
            message: empty($validationMessages)
                ? 'Payroll calculated successfully'
                : sprintf('Payroll calculated with %d warning(s)', count($validationMessages))
        );
    }

    /**
     * @skeleton
     */
    private function generatePayslip(
        PayrollCalculationRequest $request,
        $grossPay,
        $netPay,
        array $earnings,
        array $deductions
    ): string {
        // TODO: Implement via Nexus\Payroll PayrollManager
        // This should generate and persist payslip record
        
        // Generate a cryptographically secure UUIDv4 for payslipId
        $bytes = random_bytes(16);
        $bytes[6] = chr(ord($bytes[6]) & 0x0f | 0x40); // set version to 0100
        $bytes[8] = chr(ord($bytes[8]) & 0x3f | 0x80); // set bits 6-7 to 10
        $uuid = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
        return 'PAYSLIP-' . $uuid;
    }
}
