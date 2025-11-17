<?php

declare(strict_types=1);

namespace Nexus\PayrollMysStatutory\Calculators;

use Nexus\Payroll\Contracts\DeductionResultInterface;
use Nexus\Payroll\Contracts\PayloadInterface;
use Nexus\Payroll\Contracts\StatutoryCalculatorInterface;
use Nexus\Payroll\Exceptions\PayloadValidationException;
use Nexus\PayrollMysStatutory\Data\PcbTaxTable;
use Nexus\PayrollMysStatutory\Data\SocsoRateTable;
use Nexus\PayrollMysStatutory\ValueObjects\MalaysiaDeductionResult;

/**
 * Malaysia statutory payroll calculator.
 * 
 * Calculates:
 * - EPF (Employees Provident Fund) - Employee & Employer
 * - SOCSO (Social Security Organization) - Employee & Employer
 * - EIS (Employment Insurance System) - Employee & Employer
 * - PCB (Income Tax Deduction) - Employee only
 */
final readonly class MalaysiaStatutoryCalculator implements StatutoryCalculatorInterface
{
    // EPF rates
    private const EPF_EMPLOYEE_RATE = 0.11;  // 11%
    private const EPF_EMPLOYER_RATE_STANDARD = 0.12;  // 12% for salary <= RM5,000
    private const EPF_EMPLOYER_RATE_HIGH = 0.13;      // 13% for salary > RM5,000
    private const EPF_SALARY_CEILING = 30000.00;      // Maximum contributory salary
    
    // EIS rates
    private const EIS_EMPLOYEE_RATE = 0.002;  // 0.2%
    private const EIS_EMPLOYER_RATE = 0.002;  // 0.2%
    private const EIS_SALARY_CEILING = 4000.00;  // Maximum contributory salary
    
    public function calculate(PayloadInterface $payload): DeductionResultInterface
    {
        $this->validatePayload($payload);
        
        $grossPay = $payload->getGrossPay();
        $basicSalary = $payload->getBasicSalary();
        $employeeMetadata = $payload->getEmployeeMetadata();
        
        // Calculate EPF
        $epfContributions = $this->calculateEpf($grossPay);
        
        // Calculate SOCSO
        $socsoContributions = $this->calculateSocso($grossPay);
        
        // Calculate EIS
        $eisContributions = $this->calculateEis($grossPay);
        
        // Calculate PCB (income tax)
        // Taxable income is gross pay minus EPF employee contribution
        $taxableIncome = $grossPay - $epfContributions['employee'];
        $pcbAmount = $this->calculatePcb(
            $taxableIncome,
            $payload->getYtdGrossPay(),
            $payload->getYtdTaxPaid(),
            $employeeMetadata['dependents'] ?? 0,
            $employeeMetadata['marital_status'] ?? 'single'
        );
        
        // Build employee deductions breakdown
        $employeeDeductions = [
            [
                'code' => 'EPF_EMPLOYEE',
                'name' => 'EPF Employee Contribution',
                'amount' => $epfContributions['employee'],
            ],
            [
                'code' => 'SOCSO_EMPLOYEE',
                'name' => 'SOCSO Employee Contribution',
                'amount' => $socsoContributions['employee'],
            ],
            [
                'code' => 'EIS_EMPLOYEE',
                'name' => 'EIS Employee Contribution',
                'amount' => $eisContributions['employee'],
            ],
            [
                'code' => 'PCB',
                'name' => 'Income Tax (PCB)',
                'amount' => $pcbAmount,
            ],
        ];
        
        // Build employer contributions breakdown
        $employerContributions = [
            [
                'code' => 'EPF_EMPLOYER',
                'name' => 'EPF Employer Contribution',
                'amount' => $epfContributions['employer'],
            ],
            [
                'code' => 'SOCSO_EMPLOYER',
                'name' => 'SOCSO Employer Contribution',
                'amount' => $socsoContributions['employer'],
            ],
            [
                'code' => 'EIS_EMPLOYER',
                'name' => 'EIS Employer Contribution',
                'amount' => $eisContributions['employer'],
            ],
        ];
        
        // Calculation metadata for audit trail
        $metadata = [
            'country_code' => 'MY',
            'calculation_date' => date('Y-m-d H:i:s'),
            'gross_pay' => $grossPay,
            'basic_salary' => $basicSalary,
            'epf_contributory_salary' => min($grossPay, self::EPF_SALARY_CEILING),
            'epf_employee_rate' => self::EPF_EMPLOYEE_RATE,
            'epf_employer_rate' => $grossPay <= 5000 ? self::EPF_EMPLOYER_RATE_STANDARD : self::EPF_EMPLOYER_RATE_HIGH,
            'eis_contributory_salary' => min($grossPay, self::EIS_SALARY_CEILING),
            'eis_employee_rate' => self::EIS_EMPLOYEE_RATE,
            'eis_employer_rate' => self::EIS_EMPLOYER_RATE,
            'socso_bracket' => $this->getSocsoBracketDescription($grossPay),
            'pcb_taxable_income' => $taxableIncome,
            'pcb_marital_status' => $employeeMetadata['marital_status'] ?? 'single',
            'pcb_dependents' => $employeeMetadata['dependents'] ?? 0,
        ];
        
        return new MalaysiaDeductionResult(
            grossPay: $grossPay,
            employeeDeductions: $employeeDeductions,
            employerContributions: $employerContributions,
            metadata: $metadata,
        );
    }
    
    public function getSupportedCountryCode(): string
    {
        return 'MY';
    }
    
    public function getRequiredEmployeeFields(): array
    {
        return [
            'epf_number',
            'socso_number',
            'tax_number',
            'marital_status',  // 'single' or 'married'
            'dependents',      // Number of dependents for tax relief
        ];
    }
    
    public function getRequiredCompanyFields(): array
    {
        return [
            'company_registration_number',
            'epf_employer_number',
        ];
    }
    
    public function validatePayload(PayloadInterface $payload): bool
    {
        $employeeMetadata = $payload->getEmployeeMetadata();
        $companyMetadata = $payload->getCompanyMetadata();
        
        // Validate required employee fields
        foreach ($this->getRequiredEmployeeFields() as $field) {
            if (!isset($employeeMetadata[$field])) {
                throw new PayloadValidationException(
                    "Missing required employee field: {$field}"
                );
            }
        }
        
        // Validate required company fields
        foreach ($this->getRequiredCompanyFields() as $field) {
            if (!isset($companyMetadata[$field])) {
                throw new PayloadValidationException(
                    "Missing required company field: {$field}"
                );
            }
        }
        
        // Validate marital status
        if (!in_array($employeeMetadata['marital_status'], ['single', 'married'], true)) {
            throw new PayloadValidationException(
                "Invalid marital_status. Must be 'single' or 'married'."
            );
        }
        
        // Validate dependents is a non-negative integer
        if (!is_int($employeeMetadata['dependents']) || $employeeMetadata['dependents'] < 0) {
            throw new PayloadValidationException(
                "Invalid dependents. Must be a non-negative integer."
            );
        }
        
        // Validate gross pay is positive
        if ($payload->getGrossPay() <= 0) {
            throw new PayloadValidationException(
                "Gross pay must be greater than zero."
            );
        }
        
        return true;
    }
    
    /**
     * Calculate EPF employee and employer contributions.
     * 
     * @param float $grossPay Monthly gross pay
     * @return array{employee: float, employer: float}
     */
    private function calculateEpf(float $grossPay): array
    {
        $contributorySalary = min($grossPay, self::EPF_SALARY_CEILING);
        
        $employeeContribution = round($contributorySalary * self::EPF_EMPLOYEE_RATE, 2);
        
        // Employer rate depends on salary threshold
        $employerRate = $grossPay <= 5000.00 
            ? self::EPF_EMPLOYER_RATE_STANDARD 
            : self::EPF_EMPLOYER_RATE_HIGH;
        $employerContribution = round($contributorySalary * $employerRate, 2);
        
        return [
            'employee' => $employeeContribution,
            'employer' => $employerContribution,
        ];
    }
    
    /**
     * Calculate SOCSO employee and employer contributions.
     * 
     * @param float $grossPay Monthly gross pay
     * @return array{employee: float, employer: float}
     */
    private function calculateSocso(float $grossPay): array
    {
        return SocsoRateTable::getContributions($grossPay);
    }
    
    /**
     * Calculate EIS employee and employer contributions.
     * 
     * @param float $grossPay Monthly gross pay
     * @return array{employee: float, employer: float}
     */
    private function calculateEis(float $grossPay): array
    {
        $contributorySalary = min($grossPay, self::EIS_SALARY_CEILING);
        
        $employeeContribution = round($contributorySalary * self::EIS_EMPLOYEE_RATE, 2);
        $employerContribution = round($contributorySalary * self::EIS_EMPLOYER_RATE, 2);
        
        return [
            'employee' => $employeeContribution,
            'employer' => $employerContribution,
        ];
    }
    
    /**
     * Calculate PCB (monthly tax deduction).
     * 
     * @param float $monthlyTaxableIncome Taxable income (after EPF deduction)
     * @param float $ytdTaxableIncome Year-to-date taxable income
     * @param float $ytdTaxPaid Year-to-date tax paid
     * @param int $dependents Number of dependents
     * @param string $maritalStatus 'single' or 'married'
     * @return float PCB amount
     */
    private function calculatePcb(
        float $monthlyTaxableIncome,
        float $ytdTaxableIncome,
        float $ytdTaxPaid,
        int $dependents,
        string $maritalStatus
    ): float {
        return PcbTaxTable::calculateMonthlyPcb(
            $monthlyTaxableIncome,
            $ytdTaxableIncome,
            $ytdTaxPaid,
            $dependents,
            $maritalStatus
        );
    }
    
    /**
     * Get descriptive SOCSO bracket for metadata.
     * 
     * @param float $grossPay Monthly gross pay
     * @return string Bracket description
     */
    private function getSocsoBracketDescription(float $grossPay): string
    {
        if ($grossPay > 5000) {
            return 'Above RM5,000 (capped)';
        }
        
        return match(true) {
            $grossPay <= 30 => 'RM30 and below',
            $grossPay <= 50 => 'RM30.01 - RM50.00',
            $grossPay <= 100 => 'RM50.01 - RM100.00',
            $grossPay <= 500 => 'RM100.01 - RM500.00',
            $grossPay <= 1000 => 'RM500.01 - RM1,000.00',
            $grossPay <= 2000 => 'RM1,000.01 - RM2,000.00',
            $grossPay <= 3000 => 'RM2,000.01 - RM3,000.00',
            $grossPay <= 4000 => 'RM3,000.01 - RM4,000.00',
            default => 'RM4,000.01 - RM5,000.00',
        };
    }
}
