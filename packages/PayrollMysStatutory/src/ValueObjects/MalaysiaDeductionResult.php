<?php

declare(strict_types=1);

namespace Nexus\PayrollMysStatutory\ValueObjects;

use Nexus\Payroll\Contracts\DeductionResultInterface;

/**
 * Immutable result from Malaysia statutory calculations.
 */
final readonly class MalaysiaDeductionResult implements DeductionResultInterface
{
    /**
     * @param float $grossPay Gross pay before deductions
     * @param array<array{code: string, name: string, amount: float}> $employeeDeductions
     * @param array<array{code: string, name: string, amount: float}> $employerContributions
     * @param array<string, mixed> $metadata Calculation metadata for audit trail
     */
    public function __construct(
        private float $grossPay,
        private array $employeeDeductions,
        private array $employerContributions,
        private array $metadata = [],
    ) {}
    
    public function getTotalEmployeeDeductions(): float
    {
        return array_reduce(
            $this->employeeDeductions,
            fn(float $sum, array $deduction) => $sum + $deduction['amount'],
            0.00
        );
    }
    
    public function getTotalEmployerContributions(): float
    {
        return array_reduce(
            $this->employerContributions,
            fn(float $sum, array $contribution) => $sum + $contribution['amount'],
            0.00
        );
    }
    
    public function getEmployeeDeductionsBreakdown(): array
    {
        return $this->employeeDeductions;
    }
    
    public function getEmployerContributionsBreakdown(): array
    {
        return $this->employerContributions;
    }
    
    public function getNetPay(): float
    {
        return round($this->grossPay - $this->getTotalEmployeeDeductions(), 2);
    }
    
    public function getTotalCostToEmployer(): float
    {
        return round($this->grossPay + $this->getTotalEmployerContributions(), 2);
    }
    
    public function getCalculationMetadata(): array
    {
        return $this->metadata;
    }
}
