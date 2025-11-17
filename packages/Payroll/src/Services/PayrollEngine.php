<?php

declare(strict_types=1);

namespace Nexus\Payroll\Services;

use DateTimeInterface;
use Nexus\Payroll\Contracts\StatutoryCalculatorInterface;
use Nexus\Payroll\Contracts\PayslipInterface;
use Nexus\Payroll\Contracts\PayslipRepositoryInterface;
use Nexus\Payroll\Contracts\ComponentRepositoryInterface;
use Nexus\Payroll\Contracts\EmployeeComponentRepositoryInterface;
use Nexus\Payroll\Contracts\PayloadInterface;
use Nexus\Payroll\ValueObjects\PayslipStatus;

/**
 * Core country-agnostic payroll processing engine.
 */
readonly class PayrollEngine
{
    public function __construct(
        private PayslipRepositoryInterface $payslipRepository,
        private ComponentRepositoryInterface $componentRepository,
        private EmployeeComponentRepositoryInterface $employeeComponentRepository,
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
     * @return PayslipInterface Generated payslip
     */
    public function processEmployee(
        string $employeeId,
        DateTimeInterface $periodStart,
        DateTimeInterface $periodEnd
    ): PayslipInterface {
        // 1. Calculate earnings
        $earnings = $this->calculateEarnings($employeeId);
        $grossPay = array_sum(array_column($earnings, 'amount'));
        
        // 2. Calculate non-statutory deductions
        $nonStatutoryDeductions = $this->calculateNonStatutoryDeductions($employeeId);
        
        // 3. Build payload for statutory calculator
        $payload = $this->buildPayload($employeeId, $earnings, $grossPay, $periodStart, $periodEnd);
        
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
        return $this->payslipRepository->create([
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
        $employeeComponents = $this->employeeComponentRepository->getActiveComponentsForEmployee($employeeId);
        $earnings = [];
        
        foreach ($employeeComponents as $empComponent) {
            $component = $this->componentRepository->findById($empComponent->getComponentId());
            
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
        $employeeComponents = $this->employeeComponentRepository->getActiveComponentsForEmployee($employeeId);
        $deductions = [];
        
        foreach ($employeeComponents as $empComponent) {
            $component = $this->componentRepository->findById($empComponent->getComponentId());
            
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
     * Build payload for statutory calculator.
     */
    private function buildPayload(
        string $employeeId,
        array $earnings,
        float $grossPay,
        DateTimeInterface $periodStart,
        DateTimeInterface $periodEnd
    ): PayloadInterface {
        // This would be implemented by the application layer
        // Returning a mock interface reference for now
        throw new \RuntimeException("Payload building must be implemented by application layer");
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
        // This would fetch from HRM package's EmployeeManager
        throw new \RuntimeException("Employee fetching must be implemented by application layer");
    }
}
