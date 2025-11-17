<?php

declare(strict_types=1);

namespace Nexus\Payroll\Contracts;

/**
 * Input payload for statutory calculations.
 * 
 * This interface defines the standardized input structure that all
 * StatutoryCalculatorInterface implementations receive.
 */
interface PayloadInterface
{
    /**
     * Get employee ID.
     *
     * @return string Employee ULID
     */
    public function getEmployeeId(): string;
    
    /**
     * Get employee metadata (tax ID, citizenship, etc.).
     *
     * @return array<string, mixed>
     */
    public function getEmployeeMetadata(): array;
    
    /**
     * Get company metadata (registration number, tax office, etc.).
     *
     * @return array<string, mixed>
     */
    public function getCompanyMetadata(): array;
    
    /**
     * Get gross pay for the period (before any deductions).
     *
     * @return float
     */
    public function getGrossPay(): float;
    
    /**
     * Get taxable income (may differ from gross pay due to allowances/exemptions).
     *
     * @return float
     */
    public function getTaxableIncome(): float;
    
    /**
     * Get basic salary component.
     *
     * @return float
     */
    public function getBasicSalary(): float;
    
    /**
     * Get all earnings components breakdown.
     * 
     * Example:
     * [
     *     'basic_salary' => 5000.00,
     *     'housing_allowance' => 1000.00,
     *     'transport_allowance' => 500.00,
     * ]
     *
     * @return array<string, float>
     */
    public function getEarningsBreakdown(): array;
    
    /**
     * Get payroll period start date.
     *
     * @return \DateTimeInterface
     */
    public function getPeriodStart(): \DateTimeInterface;
    
    /**
     * Get payroll period end date.
     *
     * @return \DateTimeInterface
     */
    public function getPeriodEnd(): \DateTimeInterface;
    
    /**
     * Get year-to-date cumulative gross pay.
     * 
     * Used for progressive tax calculations.
     *
     * @return float
     */
    public function getYtdGrossPay(): float;
    
    /**
     * Get year-to-date cumulative tax paid.
     *
     * @return float
     */
    public function getYtdTaxPaid(): float;
    
    /**
     * Get additional metadata for statutory calculations.
     * 
     * Extensible for country-specific needs.
     *
     * @return array<string, mixed>
     */
    public function getMetadata(): array;
}
