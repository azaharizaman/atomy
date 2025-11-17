<?php

declare(strict_types=1);

namespace Nexus\Payroll\Contracts;

/**
 * Output structure from statutory calculations.
 * 
 * Contains all deductions from employee and contributions from employer.
 */
interface DeductionResultInterface
{
    /**
     * Get total employee deductions.
     *
     * @return float Sum of all employee deductions
     */
    public function getTotalEmployeeDeductions(): float;
    
    /**
     * Get total employer contributions.
     *
     * @return float Sum of all employer contributions
     */
    public function getTotalEmployerContributions(): float;
    
    /**
     * Get detailed breakdown of employee deductions.
     * 
     * Example:
     * [
     *     ['code' => 'EPF_EMPLOYEE', 'name' => 'EPF Employee', 'amount' => 550.00],
     *     ['code' => 'SOCSO_EMPLOYEE', 'name' => 'SOCSO Employee', 'amount' => 49.40],
     *     ['code' => 'PCB', 'name' => 'Income Tax (PCB)', 'amount' => 450.00],
     * ]
     *
     * @return array<array{code: string, name: string, amount: float}>
     */
    public function getEmployeeDeductionsBreakdown(): array;
    
    /**
     * Get detailed breakdown of employer contributions.
     * 
     * Example:
     * [
     *     ['code' => 'EPF_EMPLOYER', 'name' => 'EPF Employer', 'amount' => 650.00],
     *     ['code' => 'SOCSO_EMPLOYER', 'name' => 'SOCSO Employer', 'amount' => 138.60],
     *     ['code' => 'EIS_EMPLOYER', 'name' => 'EIS Employer', 'amount' => 10.00],
     * ]
     *
     * @return array<array{code: string, name: string, amount: float}>
     */
    public function getEmployerContributionsBreakdown(): array;
    
    /**
     * Get net pay (gross pay - total employee deductions).
     *
     * @return float
     */
    public function getNetPay(): float;
    
    /**
     * Get total cost to employer (gross pay + employer contributions).
     *
     * @return float
     */
    public function getTotalCostToEmployer(): float;
    
    /**
     * Get calculation metadata (rates used, formulas applied, etc.).
     * 
     * Useful for audit trail and debugging.
     *
     * @return array<string, mixed>
     */
    public function getCalculationMetadata(): array;
}
