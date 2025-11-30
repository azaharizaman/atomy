# Nexus Payroll Package

Country-agnostic atomic payroll engine for Nexus ERP.

## Features

- **Country-Agnostic Core**: No statutory logic in core package
- **Plug-and-Play Statutory Modules**: Country-specific calculations via `StatutoryCalculatorInterface`
- **Flexible Component System**: Configurable earnings, deductions, and contributions
- **Bulk Processing**: Efficient payroll runs for entire departments or companies
- **Comprehensive Payslip Generation**: Detailed breakdown with audit trails
- **Framework-Agnostic Design**: Pure PHP business logic

## Architecture

This package is designed to be **completely country-agnostic**. All statutory calculations (tax, social security, pension contributions, etc.) are delegated to external implementations of `StatutoryCalculatorInterface`.

### Example: Malaysia Statutory Package

```php
// Separate package: nexus/payroll-mys-statutory
class MalaysiaStatutoryCalculator implements StatutoryCalculatorInterface
{
    public function calculate(PayloadInterface $payload): DeductionResultInterface
    {
        // EPF, SOCSO, EIS, PCB tax calculations specific to Malaysia
    }
}
```

### Package Structure

```
src/
├── Contracts/              # Interfaces for all payroll entities
├── Services/               # Country-agnostic business logic
├── ValueObjects/           # Immutable domain value objects
└── Exceptions/             # Domain-specific exceptions
```

## Installation

```bash
composer require nexus/payroll
```

For country-specific statutory calculations:

```bash
# Malaysia
composer require nexus/payroll-mys-statutory

# Singapore
composer require nexus/payroll-sgp-statutory
```

## Usage

### Payroll Processing

```php
use Nexus\Payroll\Services\PayrollEngine;

$payrollEngine = app(PayrollEngine::class);

// Process payroll for specific period
$payslips = $payrollEngine->processPeriod(
    tenantId: $tenantId,
    periodStart: '2025-01-01',
    periodEnd: '2025-01-31',
    filters: ['department_id' => $departmentId]
);
```

### Component Management

```php
use Nexus\Payroll\Services\ComponentManager;

$componentManager = app(ComponentManager::class);

// Create earnings component
$basicSalaryComponent = $componentManager->createComponent([
    'name' => 'Basic Salary',
    'code' => 'BASIC',
    'type' => 'earning',
    'calculation_method' => 'fixed',
]);

// Create deduction component
$loanDeduction = $componentManager->createComponent([
    'name' => 'Loan Deduction',
    'code' => 'LOAN',
    'type' => 'deduction',
    'calculation_method' => 'fixed_amount',
]);
```

### Statutory Calculator Registration

```php
// In your application service provider
use Nexus\Payroll\Contracts\StatutoryCalculatorInterface;
use App\Payroll\MalaysiaStatutoryCalculator;

$this->app->singleton(StatutoryCalculatorInterface::class, function ($app) {
    return new MalaysiaStatutoryCalculator(
        epfRate: 0.11,
        employerEpfRate: 0.13,
        // ... other Malaysian statutory rates
    );
});
```

## Key Contracts

### StatutoryCalculatorInterface

The core interface for country-specific implementations:

```php
interface StatutoryCalculatorInterface
{
    public function calculate(PayloadInterface $payload): DeductionResultInterface;
    public function getSupportedCountryCode(): string;
    public function getRequiredEmployeeFields(): array;
}
```

### PayloadInterface

Input data structure for statutory calculations:

```php
interface PayloadInterface
{
    public function getEmployeeId(): string;
    public function getGrossPay(): float;
    public function getTaxableIncome(): float;
    public function getMetadata(): array;
}
```

### DeductionResultInterface

Output structure from statutory calculations:

```php
interface DeductionResultInterface
{
    public function getTotalDeductions(): float;
    public function getBreakdown(): array;
    public function getEmployerContributions(): array;
}
```

## Requirements

- PHP 8.3 or higher
- Integration with `Nexus\Hrm` for employee data
- Integration with `Nexus\Accounting` for GL posting

---

## 📖 Documentation

### Package Documentation
- **[Getting Started Guide](docs/getting-started.md)** - Quick start guide with prerequisites, concepts, and first integration
- **[API Reference](docs/api-reference.md)** - Complete documentation of all interfaces, value objects, and exceptions
- **[Integration Guide](docs/integration-guide.md)** - Laravel and Symfony integration examples
- **[Basic Usage Example](docs/examples/basic-usage.php)** - Simple usage patterns
- **[Advanced Usage Example](docs/examples/advanced-usage.php)** - Advanced scenarios and patterns

### Additional Resources
- `IMPLEMENTATION_SUMMARY.md` - Implementation progress and metrics
- `REQUIREMENTS.md` - Detailed requirements
- `TEST_SUITE_SUMMARY.md` - Test coverage and results
- `VALUATION_MATRIX.md` - Package valuation metrics
- See root `ARCHITECTURE.md` for overall system architecture

---

## License

MIT License - see LICENSE file for details.
