# Nexus Payroll - Malaysia Statutory Calculator

Country-specific statutory payroll calculations for Malaysia, implementing the `Nexus\Payroll` package's `StatutoryCalculatorInterface`.

## Features

This package calculates Malaysian statutory deductions and employer contributions:

- **EPF (Employees Provident Fund)** - Employee and employer contributions
- **SOCSO (Social Security Organization)** - Employee and employer contributions  
- **EIS (Employment Insurance System)** - Employee and employer contributions
- **PCB (Income Tax Deduction)** - Monthly tax estimate (MTD)

## Calculation Rules (2024/2025)

### EPF
- **Employee:** 11% of monthly salary
- **Employer:** 12% or 13% depending on salary (12% for salary ≤ RM5,000, 13% for > RM5,000)
- **Salary Ceiling:** RM30,000 per month

### SOCSO
- **Contribution Categories:** Based on monthly salary ranges (RM30 to RM5,000)
- **Maximum Contribution:** RM234.30 per month (for salary > RM4,950)
- Both employee and employer contributions apply

### EIS
- **Employee:** 0.2% of monthly salary (capped)
- **Employer:** 0.2% of monthly salary (capped)
- **Salary Ceiling:** RM4,000 per month
- **Maximum Contribution:** RM7.90 per month

### PCB (Income Tax)
- Calculated based on MTD (Monthly Tax Deduction) tables
- Considers tax relief, marital status, and dependents
- Progressive tax rates apply

## Usage

```php
use Nexus\PayrollMysStatutory\MalaysiaStatutoryCalculator;
use Nexus\Payroll\Contracts\PayloadInterface;

$calculator = new MalaysiaStatutoryCalculator();

// Create payload with employee and company data
$payload = new StandardPayload(
    employeeId: '01234567-89ab-cdef-0123-456789abcdef',
    employeeMetadata: [
        'tax_number' => 'SG1234567890',
        'socso_number' => '12345678',
        'epf_number' => '87654321',
        'marital_status' => 'married',
        'dependents' => 2,
    ],
    companyMetadata: [
        'company_registration_number' => '1234567-X',
        'epf_employer_number' => 'EMPLOYER123',
    ],
    grossPay: 5000.00,
    taxableIncome: 5000.00,
    basicSalary: 4000.00,
    earningsBreakdown: [
        ['code' => 'BASIC', 'name' => 'Basic Salary', 'amount' => 4000.00],
        ['code' => 'ALLOWANCE', 'name' => 'Transport Allowance', 'amount' => 1000.00],
    ],
    periodStart: new \DateTime('2025-01-01'),
    periodEnd: new \DateTime('2025-01-31'),
    ytdGrossPay: 60000.00,
    ytdTaxPaid: 1200.00,
);

// Calculate statutory deductions
$result = $calculator->calculate($payload);

// Get results
$totalEmployeeDeductions = $result->getTotalEmployeeDeductions(); // EPF + SOCSO + EIS + PCB
$totalEmployerContributions = $result->getTotalEmployerContributions(); // EPF + SOCSO + EIS
$netPay = $result->getNetPay();

// Get breakdown
$employeeDeductions = $result->getEmployeeDeductionsBreakdown();
// [
//     ['code' => 'EPF_EMPLOYEE', 'name' => 'EPF Employee Contribution', 'amount' => 550.00],
//     ['code' => 'SOCSO_EMPLOYEE', 'name' => 'SOCSO Employee Contribution', 'amount' => 24.75],
//     ['code' => 'EIS_EMPLOYEE', 'name' => 'EIS Employee Contribution', 'amount' => 7.90],
//     ['code' => 'PCB', 'name' => 'Income Tax (PCB)', 'amount' => 125.00],
// ]

$employerContributions = $result->getEmployerContributionsBreakdown();
// [
//     ['code' => 'EPF_EMPLOYER', 'name' => 'EPF Employer Contribution', 'amount' => 650.00],
//     ['code' => 'SOCSO_EMPLOYER', 'name' => 'SOCSO Employer Contribution', 'amount' => 84.75],
//     ['code' => 'EIS_EMPLOYER', 'name' => 'EIS Employer Contribution', 'amount' => 7.90],
// ]
```

## Integration with Nexus Payroll

Register the calculator in your service provider:

```php
use Nexus\PayrollMysStatutory\MalaysiaStatutoryCalculator;
use App\Services\Payroll\TenantAwareStatutoryCalculator;

public function register(): void
{
    $this->app->singleton(StatutoryCalculatorInterface::class, function ($app) {
        $tenantCalculator = new TenantAwareStatutoryCalculator();
        
        // Register Malaysia calculator
        $tenantCalculator->registerCalculator('MY', new MalaysiaStatutoryCalculator());
        
        // Set as default for Malaysian tenants
        $tenantCalculator->setDefaultCountryCode('MY');
        
        return $tenantCalculator;
    });
}
```

## Required Employee Metadata Fields

- `epf_number` - EPF membership number
- `socso_number` - SOCSO registration number
- `tax_number` - Income tax reference number
- `marital_status` - 'single' or 'married' (for PCB calculation)
- `dependents` - Number of dependents (for PCB calculation)

## Required Company Metadata Fields

- `company_registration_number` - SSM registration number
- `epf_employer_number` - EPF employer reference number

## Official References

- **EPF:** https://www.kwsp.gov.my/
- **SOCSO:** https://www.perkeso.gov.my/
- **EIS:** https://www.perkeso.gov.my/en/eis.html
- **LHDN (Inland Revenue):** https://www.hasil.gov.my/

## Notes

- Calculations are based on 2024/2025 statutory rates
- EPF, SOCSO, and EIS rates may change annually - update accordingly
- PCB calculations use simplified MTD tables - consult tax advisor for complex scenarios
- Foreign workers may have different contribution rules

## 📖 Documentation

### Package Documentation
- [Getting Started Guide](docs/getting-started.md)
- [API Reference](docs/api-reference.md)
- [Integration Guide](docs/integration-guide.md)
- [Examples](docs/examples/)

### Additional Resources
- `IMPLEMENTATION_SUMMARY.md` - Implementation progress
- `REQUIREMENTS.md` - Requirements
- `TEST_SUITE_SUMMARY.md` - Tests
- `VALUATION_MATRIX.md` - Valuation
