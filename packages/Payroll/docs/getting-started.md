# Getting Started with Nexus Payroll

## Prerequisites

- PHP 8.3 or higher
- Composer
- A consuming application (Laravel, Symfony, or any PHP framework)
- Implementations for repository interfaces

## Installation

```bash
composer require nexus/payroll:"*@dev"
```

For country-specific statutory calculations, install the appropriate package:

```bash
# Malaysia (EPF, SOCSO, EIS, PCB)
composer require nexus/payroll-mys-statutory:"*@dev"

# Singapore (CPF, SDL) - Coming soon
# composer require nexus/payroll-sgp-statutory:"*@dev"
```

---

## When to Use This Package

This package is designed for:

- ✅ Processing payroll for employees with configurable components
- ✅ Managing earnings, deductions, and employer contributions
- ✅ Generating payslips with complete breakdown
- ✅ Multi-country payroll with pluggable statutory calculators
- ✅ Integration with HRM systems for employee data
- ✅ Integration with accounting systems for GL posting

Do NOT use this package for:

- ❌ Direct database operations (use repository implementations)
- ❌ API endpoint handling (build in consuming application)
- ❌ PDF/document generation (use `Nexus\Export`)
- ❌ Email notifications (use `Nexus\Notifier`)

---

## Core Concepts

### 1. Country-Agnostic Design

The core payroll package contains **no statutory calculation logic**. All country-specific calculations (tax, social security, pension) are delegated to external implementations of `StatutoryCalculatorInterface`.

```
┌─────────────────────────────────────────────────────────────┐
│                     Nexus\Payroll                            │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐          │
│  │ PayrollEngine│  │ComponentMgr │  │ PayslipMgr  │          │
│  └──────┬──────┘  └─────────────┘  └─────────────┘          │
│         │                                                    │
│         │ PayloadInterface                                   │
│         ▼                                                    │
│  ┌─────────────────────────────────────────────────┐        │
│  │         StatutoryCalculatorInterface            │        │
│  └─────────────────────────────────────────────────┘        │
└─────────────────────────────────────────────────────────────┘
                              │
          ┌───────────────────┼───────────────────┐
          ▼                   ▼                   ▼
   ┌─────────────┐     ┌─────────────┐     ┌─────────────┐
   │ Malaysia    │     │ Singapore   │     │ Indonesia   │
   │ Calculator  │     │ Calculator  │     │ Calculator  │
   └─────────────┘     └─────────────┘     └─────────────┘
```

### 2. CQRS Repository Pattern

All persistence operations are separated into Query (read) and Persist (write) interfaces:

| Operation Type | Interface Pattern |
|---------------|-------------------|
| Read (find, get, list) | `*QueryInterface` |
| Write (create, update, delete) | `*PersistInterface` |

This allows services to depend only on the operations they need.

### 3. Payroll Components

Components represent earnings, deductions, or employer contributions:

| Component Type | Examples |
|---------------|----------|
| `EARNING` | Basic Salary, Overtime, Bonus, Allowances |
| `DEDUCTION` | Loan Repayment, Advance Recovery, Union Dues |
| `EMPLOYER_CONTRIBUTION` | EPF Employer, SOCSO Employer, Insurance |

### 4. Calculation Methods

Components support different calculation methods:

| Method | Description | Example |
|--------|-------------|---------|
| `FIXED` | Fixed amount per period | Basic Salary: RM5,000 |
| `PERCENTAGE_OF_BASIC` | Percentage of basic salary | Housing: 10% of basic |
| `PERCENTAGE_OF_GROSS` | Percentage of gross pay | Commission: 2% of gross |
| `FORMULA` | Custom expression (pending) | Complex calculations |

---

## Basic Configuration

### Step 1: Create Repository Implementations

Implement the repository interfaces in your application layer:

```php
<?php

declare(strict_types=1);

namespace App\Repositories\Payroll;

use Nexus\Payroll\Contracts\ComponentQueryInterface;
use Nexus\Payroll\Contracts\ComponentPersistInterface;
use Nexus\Payroll\Contracts\ComponentInterface;
use App\Models\PayrollComponent;

final readonly class EloquentComponentRepository 
    implements ComponentQueryInterface, ComponentPersistInterface
{
    public function findById(string $id): ?ComponentInterface
    {
        return PayrollComponent::find($id);
    }
    
    public function findByCode(string $code): ?ComponentInterface
    {
        return PayrollComponent::where('code', $code)->first();
    }
    
    public function getActiveComponents(): array
    {
        return PayrollComponent::where('is_active', true)->get()->all();
    }
    
    public function create(array $data): ComponentInterface
    {
        return PayrollComponent::create($data);
    }
    
    public function update(string $id, array $data): ComponentInterface
    {
        $component = PayrollComponent::findOrFail($id);
        $component->update($data);
        return $component;
    }
    
    public function delete(string $id): bool
    {
        return PayrollComponent::destroy($id) > 0;
    }
}
```

### Step 2: Bind Interfaces in Service Provider

#### Laravel

```php
<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Nexus\Payroll\Contracts\ComponentQueryInterface;
use Nexus\Payroll\Contracts\ComponentPersistInterface;
use Nexus\Payroll\Contracts\PayslipQueryInterface;
use Nexus\Payroll\Contracts\PayslipPersistInterface;
use Nexus\Payroll\Contracts\EmployeeComponentQueryInterface;
use Nexus\Payroll\Contracts\EmployeeComponentPersistInterface;
use Nexus\Payroll\Contracts\StatutoryCalculatorInterface;
use Nexus\Payroll\Services\PayrollEngine;
use Nexus\Payroll\Services\ComponentManager;
use Nexus\Payroll\Services\PayslipManager;
use App\Repositories\Payroll\EloquentComponentRepository;
use App\Repositories\Payroll\EloquentPayslipRepository;
use App\Repositories\Payroll\EloquentEmployeeComponentRepository;
use Nexus\PayrollMysStatutory\Calculators\MalaysiaStatutoryCalculator;

class PayrollServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // CQRS: Bind Query and Persist interfaces
        $this->app->singleton(ComponentQueryInterface::class, EloquentComponentRepository::class);
        $this->app->singleton(ComponentPersistInterface::class, EloquentComponentRepository::class);
        
        $this->app->singleton(PayslipQueryInterface::class, EloquentPayslipRepository::class);
        $this->app->singleton(PayslipPersistInterface::class, EloquentPayslipRepository::class);
        
        $this->app->singleton(EmployeeComponentQueryInterface::class, EloquentEmployeeComponentRepository::class);
        $this->app->singleton(EmployeeComponentPersistInterface::class, EloquentEmployeeComponentRepository::class);
        
        // Bind statutory calculator (country-specific)
        $this->app->singleton(StatutoryCalculatorInterface::class, MalaysiaStatutoryCalculator::class);
        
        // Services are auto-resolved via constructor injection
        $this->app->singleton(PayrollEngine::class);
        $this->app->singleton(ComponentManager::class);
        $this->app->singleton(PayslipManager::class);
    }
}
```

#### Symfony

```yaml
# config/services.yaml
services:
    # Repositories
    Nexus\Payroll\Contracts\ComponentQueryInterface:
        class: App\Repository\Payroll\DoctrineComponentRepository
    Nexus\Payroll\Contracts\ComponentPersistInterface:
        class: App\Repository\Payroll\DoctrineComponentRepository
    
    Nexus\Payroll\Contracts\PayslipQueryInterface:
        class: App\Repository\Payroll\DoctrinePayslipRepository
    Nexus\Payroll\Contracts\PayslipPersistInterface:
        class: App\Repository\Payroll\DoctrinePayslipRepository
    
    # Statutory Calculator
    Nexus\Payroll\Contracts\StatutoryCalculatorInterface:
        class: Nexus\PayrollMysStatutory\Calculators\MalaysiaStatutoryCalculator
    
    # Services
    Nexus\Payroll\Services\PayrollEngine:
        autowire: true
    Nexus\Payroll\Services\ComponentManager:
        autowire: true
    Nexus\Payroll\Services\PayslipManager:
        autowire: true
```

### Step 3: Use the Package

```php
<?php

declare(strict_types=1);

namespace App\Services;

use Nexus\Payroll\Services\PayrollEngine;
use Nexus\Payroll\Services\ComponentManager;

final readonly class PayrollService
{
    public function __construct(
        private PayrollEngine $payrollEngine,
        private ComponentManager $componentManager
    ) {}
    
    public function processMonthlyPayroll(string $tenantId, string $periodStart, string $periodEnd): array
    {
        return $this->payrollEngine->processPeriod(
            tenantId: $tenantId,
            periodStart: $periodStart,
            periodEnd: $periodEnd
        );
    }
    
    public function createComponent(array $data): ComponentInterface
    {
        return $this->componentManager->createComponent($data);
    }
}
```

---

## Your First Integration

Complete working example for processing payroll:

```php
<?php

declare(strict_types=1);

use Nexus\Payroll\Services\PayrollEngine;
use Nexus\Payroll\Services\ComponentManager;
use Nexus\Payroll\ValueObjects\ComponentType;
use Nexus\Payroll\ValueObjects\CalculationMethod;

// 1. Create a payroll component (usually done once during setup)
$componentManager = app(ComponentManager::class);

$basicSalary = $componentManager->createComponent([
    'tenant_id' => 'tenant-001',
    'code' => 'BASIC',
    'name' => 'Basic Salary',
    'type' => ComponentType::EARNING,
    'calculation_method' => CalculationMethod::FIXED,
    'is_taxable' => true,
    'is_statutory' => true,
    'is_active' => true,
]);

// 2. Assign component to employee (usually via EmployeeComponentManager)
// This links the component with a specific amount for an employee

// 3. Process payroll for a period
$payrollEngine = app(PayrollEngine::class);

$payslips = $payrollEngine->processPeriod(
    tenantId: 'tenant-001',
    periodStart: '2025-01-01',
    periodEnd: '2025-01-31',
    filters: [
        'department_id' => 'dept-001',  // Optional filter
    ]
);

// 4. Review generated payslips
foreach ($payslips as $payslip) {
    echo sprintf(
        "Employee: %s | Gross: %.2f | Net: %.2f | Status: %s\n",
        $payslip->getEmployeeId(),
        $payslip->getGrossPay(),
        $payslip->getNetPay(),
        $payslip->getStatus()->value
    );
}
```

---

## Next Steps

- **[API Reference](api-reference.md)** - Detailed documentation of all interfaces and methods
- **[Integration Guide](integration-guide.md)** - Laravel and Symfony integration examples
- **[Basic Usage Example](examples/basic-usage.php)** - Complete code example
- **[Advanced Usage Example](examples/advanced-usage.php)** - Multi-country, bulk processing

---

## Troubleshooting

### Common Issues

**Issue 1: Interface not bound error**

```
Target interface [Nexus\Payroll\Contracts\ComponentQueryInterface] is not instantiable.
```

- **Cause:** Repository interface not bound in service container
- **Solution:** Add binding in service provider (see Step 2 above)

**Issue 2: Statutory calculator not found**

```
Target interface [Nexus\Payroll\Contracts\StatutoryCalculatorInterface] is not instantiable.
```

- **Cause:** No country-specific calculator package installed
- **Solution:** Install appropriate package: `composer require nexus/payroll-mys-statutory`

**Issue 3: Empty payslips array returned**

- **Cause:** No active employee components for the period
- **Solution:** Verify employee components are assigned with effective dates within the period
