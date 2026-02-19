<?php

declare(strict_types=1);

namespace Nexus\Payroll\Services;

use DateTimeInterface;
use Nexus\Payroll\Contracts\EmployeeDataProviderInterface;
use Nexus\Payroll\Contracts\PayloadBuilderInterface;
use Nexus\Payroll\Contracts\StatutoryCalculatorInterface;
use Nexus\Payroll\Contracts\PayslipInterface;
use Nexus\Payroll\Contracts\PayslipQueryInterface;
use Nexus\Payroll\Contracts\PayslipPersistInterface;
use Nexus\Payroll\Contracts\ComponentQueryInterface;
use Nexus\Payroll\Contracts\EmployeeComponentQueryInterface;
use Nexus\Payroll\Contracts\PayloadInterface;
use Nexus\Payroll\ValueObjects\PayslipStatus;

/**
 * Core country-agnostic payroll processing engine.
 */
final readonly class PayrollEngine
{
    public function __construct(
        private EmployeeDataProviderInterface $employeeDataProvider,
        private PayslipQueryInterface $payslipQuery,
        private PayslipPersistInterface $payslipPersist,
        private ComponentQueryInterface $componentQuery,
        private EmployeeComponentQueryInterface $employeeComponentQuery,
        private PayloadBuilderInterface $payloadBuilder,
        private StatutoryCalculatorInterface $statutoryCalculator,
    ) {
    }
    
    /**
     * Process payroll for a specific period.
     *
     * @param string $tenantId Tenant ULID
     * @param string|DateTimeInterface $periodStart Period start date
     * @param string|DateTimeInterface $periodEnd Period end date
     * @param array<string, mixed> $filters Optional filters (employee_ids, department_id, etc.)
     * @return array<PayslipInterface> Generated payslips
     */
    public function processPeriod(
        string $tenantId,
        $periodStart,
        $periodEnd,
        array $filters = []
    ): array {
        if (is_string($periodStart)) {
            $periodStart = new \DateTime($periodStart);
        }
        if (is_string($periodEnd)) {
            $periodEnd = new \DateTime($periodEnd);
        }
        
        // Get employees to process (from filters or all active employees)
        $employeeIds = $filters['employee_ids'] ?? $this->getAllActiveEmployeeIds($tenantId, $filters);
        
        $payslips = [];
        
        foreach ($employeeIds as $employeeId) {
            $payslips[] = $this->processEmployee($employeeId, $periodStart, $periodEnd);
        }
        
        return $payslips;
    }
    
    /**
     * Process payroll for a single employee.
     *
     * @param string $employeeId Employee ULID
     * @param DateTimeInterface $periodStart Period start date
     * @param DateTimeInterface $periodEnd Period end date
     * @param string|null $tenantId Tenant ULID (optional, defaults to extracting from employee)
     * @return PayslipInterface Generated payslip
     */
    public function processEmployee(
        string $employeeId,
        DateTimeInterface $periodStart,
        DateTimeInterface $periodEnd,
        ?string $tenantId = null
    ): PayslipInterface {
        // 1. Calculate earnings
        $earnings = $this->calculateEarnings($employeeId);
        $grossPay = array_sum(array_column($earnings, 'amount'));
        
        // 2. Calculate non-statutory deductions
        $nonStatutoryDeductions = $this->calculateNonStatutoryDeductions($employeeId);
        
        // 3. Build payload for statutory calculator using PayloadBuilder
        $options = $tenantId ? ['tenantId' => $tenantId] : [];
        $payload = $this->payloadBuilder->buildPayload(
            $employeeId,
            $periodStart,
            $periodEnd,
            $options
        );
        
        // 4. Calculate statutory deductions and employer contributions
        $statutoryResult = $this->statutoryCalculator->calculate($payload);
        
        // 5. Combine all deductions
        $allDeductions = array_merge(
            $nonStatutoryDeductions,
            $statutoryResult->getEmployeeDeductionsBreakdown()
        );
        
        $totalDeductions = array_sum(array_column($allDeductions, 'amount'));
        $netPay = $grossPay - $totalDeductions;
        
        // 6. Create payslip
        return $this->payslipPersist->create([
            'employee_id' => $employeeId,
            'period_start' => $periodStart->format('Y-m-d'),
            'period_end' => $periodEnd->format('Y-m-d'),
            'pay_date' => $this->calculatePayDate($periodEnd)->format('Y-m-d'),
            'gross_pay' => $grossPay,
            'total_earnings' => $grossPay,
            'total_deductions' => $totalDeductions,
            'net_pay' => $netPay,
            'earnings_breakdown' => $earnings,
            'deductions_breakdown' => $allDeductions,
            'employer_contributions' => $statutoryResult->getEmployerContributionsBreakdown(),
            'status' => PayslipStatus::DRAFT->value,
            'metadata' => [
                'calculation_metadata' => $statutoryResult->getCalculationMetadata(),
                'total_cost_to_employer' => $statutoryResult->getTotalCostToEmployer(),
            ],
        ]);
    }
    
    /**
     * Calculate all earnings for employee.
     *
     * @param string $employeeId Employee ULID
     * @return array<array{code: string, name: string, amount: float, is_taxable: bool}>
     */
    private function calculateEarnings(string $employeeId): array
    {
        $employeeComponents = $this->employeeComponentQuery->getActiveComponentsForEmployee($employeeId);
        $earnings = [];
        
        foreach ($employeeComponents as $empComponent) {
            $component = $this->componentQuery->findById($empComponent->getComponentId());
            
            if (!$component || $component->getType() !== 'earning') {
                continue;
            }
            
            $amount = $this->calculateComponentAmount($empComponent, $component);
            
            $earnings[] = [
                'code' => $component->getCode(),
                'name' => $component->getName(),
                'amount' => $amount,
                'is_taxable' => $component->isTaxable(),
            ];
        }
        
        return $earnings;
    }
    
    /**
     * Calculate non-statutory deductions.
     *
     * @param string $employeeId Employee ULID
     * @return array<array{code: string, name: string, amount: float}>
     */
    private function calculateNonStatutoryDeductions(string $employeeId): array
    {
        $employeeComponents = $this->employeeComponentQuery->getActiveComponentsForEmployee($employeeId);
        $deductions = [];
        
        foreach ($employeeComponents as $empComponent) {
            $component = $this->componentQuery->findById($empComponent->getComponentId());
            
            if (!$component || $component->getType() !== 'deduction' || $component->isStatutory()) {
                continue;
            }
            
            $amount = $this->calculateComponentAmount($empComponent, $component);
            
            $deductions[] = [
                'code' => $component->getCode(),
                'name' => $component->getName(),
                'amount' => $amount,
            ];
        }
        
        return $deductions;
    }
    
    /**
     * Calculate component amount based on calculation method.
     *
     * @param mixed $empComponent Employee component assignment
     * @param mixed $component Component definition
     * @return float Calculated amount
     */
    private function calculateComponentAmount($empComponent, $component): float
    {
        // If employee component has override amount, use it
        if ($empComponent->getAmount() !== null) {
            return $empComponent->getAmount();
        }
        
        return match($component->getCalculationMethod()) {
            'fixed_amount' => $component->getFixedAmount() ?? 0.0,
            default => 0.0, // Other methods would need more context
        };
    }
    
    /**
     * Build payload for statutory calculator using PayloadBuilder.
     * 
     * @deprecated Use PayloadBuilderInterface directly instead
     */
    private function buildPayload(
        string $employeeId,
        array $earnings,
        float $grossPay,
        DateTimeInterface $periodStart,
        DateTimeInterface $periodEnd
    ): PayloadInterface {
        // Delegate to the injected PayloadBuilder
        // This method is kept for backward compatibility
        return $this->payloadBuilder->buildPayload(
            $employeeId,
            $periodStart,
            $periodEnd,
            []
        );
    }
    
    /**
     * Calculate pay date based on period end.
     */
    private function calculatePayDate(DateTimeInterface $periodEnd): DateTimeInterface
    {
        // Typically salary is paid on last day of month or first day of next month
        $payDate = clone $periodEnd;
        return $payDate;
    }
    
    /**
     * Get all active employee IDs for tenant with filters.
     */
    private function getAllActiveEmployeeIds(string $tenantId, array $filters): array
    {
        $effectiveDate = $filters['effective_date'] ?? new \DateTime();
        
        // Use EmployeeDataProvider to get active employees
        $employees = $this->employeeDataProvider->getActiveEmployees($tenantId, $effectiveDate);
        
        // Filter by department if specified
        if (isset($filters['department_id'])) {
            $employees = array_filter($employees, fn($e) => 
                ($e->metadata['department_id'] ?? null) === $filters['department_id']
            );
        }
        
        return array_column($employees, 'employeeId');
    }
}
