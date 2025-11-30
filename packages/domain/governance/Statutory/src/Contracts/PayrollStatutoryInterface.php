<?php

declare(strict_types=1);

namespace Nexus\Statutory\Contracts;

/**
 * Interface for payroll statutory calculations.
 * 
 * Country-specific implementations provide calculations for mandatory deductions
 * (EPF, SOCSO, EIS, income tax, etc.) based on local regulations.
 */
interface PayrollStatutoryInterface
{
    /**
     * Calculate all statutory deductions for an employee.
     *
     * @param string $tenantId The tenant identifier
     * @param string $employeeId The employee identifier
     * @param float $grossSalary The gross salary amount
     * @param \DateTimeImmutable $payDate The payment date
     * @param array<string, mixed> $employeeData Additional employee data
     * @return array<string, float> Deduction amounts keyed by deduction type
     * @throws \Nexus\Statutory\Exceptions\CalculationException
     */
    public function calculateDeductions(
        string $tenantId,
        string $employeeId,
        float $grossSalary,
        \DateTimeImmutable $payDate,
        array $employeeData = []
    ): array;

    /**
     * Get the country code for this statutory calculator.
     *
     * @return string ISO 3166-1 alpha-3 code (e.g., 'MYS', 'SGP')
     */
    public function getCountryCode(): string;

    /**
     * Get all supported deduction types for this country.
     *
     * @return array<string> Deduction type identifiers
     */
    public function getSupportedDeductionTypes(): array;

    /**
     * Get the effective rate for a specific deduction type.
     *
     * @param string $deductionType The deduction type
     * @param float $grossSalary The gross salary
     * @param \DateTimeImmutable $effectiveDate The effective date
     * @return float The rate (percentage or absolute amount)
     * @throws \Nexus\Statutory\Exceptions\InvalidDeductionTypeException
     */
    public function getEffectiveRate(
        string $deductionType,
        float $grossSalary,
        \DateTimeImmutable $effectiveDate
    ): float;
}
