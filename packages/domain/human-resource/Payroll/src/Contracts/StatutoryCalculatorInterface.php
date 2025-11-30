<?php

declare(strict_types=1);

namespace Nexus\Payroll\Contracts;

/**
 * Critical contract for country-specific statutory calculations.
 * 
 * Implementations of this interface handle all country-specific logic:
 * - Tax calculations (income tax, withholding tax)
 * - Social security contributions
 * - Pension/retirement fund contributions
 * - Health insurance deductions
 * - Any other mandatory statutory deductions
 * 
 * Examples:
 * - Malaysia: EPF, SOCSO, EIS, PCB tax
 * - Singapore: CPF, SDL
 * - Indonesia: BPJS, PPh 21
 */
interface StatutoryCalculatorInterface
{
    /**
     * Calculate all statutory deductions and contributions.
     *
     * @param PayloadInterface $payload Input data for calculation
     * @return DeductionResultInterface Breakdown of all deductions and contributions
     */
    public function calculate(PayloadInterface $payload): DeductionResultInterface;
    
    /**
     * Get the ISO country code this calculator supports.
     *
     * @return string ISO 3166-1 alpha-2 country code (e.g., 'MY', 'SG', 'ID')
     */
    public function getSupportedCountryCode(): string;
    
    /**
     * Get list of required employee metadata fields.
     * 
     * Used for validation before calculation.
     * Example: ['tax_number', 'social_security_number', 'citizenship_status']
     *
     * @return array<string> Array of required field names
     */
    public function getRequiredEmployeeFields(): array;
    
    /**
     * Get list of required company metadata fields.
     * 
     * Example: ['company_registration_number', 'tax_office_code']
     *
     * @return array<string> Array of required field names
     */
    public function getRequiredCompanyFields(): array;
    
    /**
     * Validate payload before calculation.
     *
     * @param PayloadInterface $payload Input data
     * @return bool True if valid
     * @throws \Nexus\Payroll\Exceptions\PayloadValidationException If validation fails
     */
    public function validatePayload(PayloadInterface $payload): bool;
}
