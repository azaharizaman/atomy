# API Reference: Payroll

This document provides complete API documentation for all interfaces, services, value objects, and exceptions in the `Nexus\Payroll` package.

---

## Table of Contents

1. [Entity Interfaces](#entity-interfaces)
2. [Repository Interfaces (CQRS)](#repository-interfaces-cqrs)
3. [Statutory Calculation Interfaces](#statutory-calculation-interfaces)
4. [Service Classes](#service-classes)
5. [Value Objects / Enums](#value-objects--enums)
6. [Exceptions](#exceptions)

---

## Entity Interfaces

### ComponentInterface

**Location:** `src/Contracts/ComponentInterface.php`

**Purpose:** Defines the contract for payroll components (earnings, deductions, contributions).

```php
<?php

namespace Nexus\Payroll\Contracts;

use Nexus\Payroll\ValueObjects\ComponentType;
use Nexus\Payroll\ValueObjects\CalculationMethod;

interface ComponentInterface
{
    /**
     * Get component unique identifier (ULID)
     */
    public function getId(): string;
    
    /**
     * Get tenant identifier for multi-tenancy
     */
    public function getTenantId(): string;
    
    /**
     * Get component code (e.g., BASIC, OT, EPF)
     */
    public function getCode(): string;
    
    /**
     * Get human-readable component name
     */
    public function getName(): string;
    
    /**
     * Get component type (earning, deduction, employer contribution)
     */
    public function getType(): ComponentType;
    
    /**
     * Get calculation method (fixed, percentage, formula)
     */
    public function getCalculationMethod(): CalculationMethod;
    
    /**
     * Check if component is subject to tax
     */
    public function isTaxable(): bool;
    
    /**
     * Check if component contributes to statutory calculations
     */
    public function isStatutory(): bool;
    
    /**
     * Check if component is currently active
     */
    public function isActive(): bool;
}
```

---

### EmployeeComponentInterface

**Location:** `src/Contracts/EmployeeComponentInterface.php`

**Purpose:** Defines the contract for employee-specific component assignments.

```php
<?php

namespace Nexus\Payroll\Contracts;

interface EmployeeComponentInterface
{
    /**
     * Get assignment unique identifier (ULID)
     */
    public function getId(): string;
    
    /**
     * Get tenant identifier
     */
    public function getTenantId(): string;
    
    /**
     * Get employee identifier
     */
    public function getEmployeeId(): string;
    
    /**
     * Get linked component identifier
     */
    public function getComponentId(): string;
    
    /**
     * Get component amount for this employee
     */
    public function getAmount(): float;
    
    /**
     * Get date when component becomes effective
     */
    public function getEffectiveFrom(): \DateTimeImmutable;
    
    /**
     * Get date when component expires (null = no expiry)
     */
    public function getEffectiveTo(): ?\DateTimeImmutable;
    
    /**
     * Check if component is active for a given date
     */
    public function isActiveOn(\DateTimeImmutable $date): bool;
}
```

---

### PayslipInterface

**Location:** `src/Contracts/PayslipInterface.php`

**Purpose:** Defines the contract for generated payslips.

```php
<?php

namespace Nexus\Payroll\Contracts;

use Nexus\Payroll\ValueObjects\PayslipStatus;

interface PayslipInterface
{
    /**
     * Get payslip unique identifier (ULID)
     */
    public function getId(): string;
    
    /**
     * Get tenant identifier
     */
    public function getTenantId(): string;
    
    /**
     * Get payslip number (human-readable reference)
     */
    public function getPayslipNumber(): string;
    
    /**
     * Get employee identifier
     */
    public function getEmployeeId(): string;
    
    /**
     * Get pay period start date
     */
    public function getPeriodStart(): \DateTimeImmutable;
    
    /**
     * Get pay period end date
     */
    public function getPeriodEnd(): \DateTimeImmutable;
    
    /**
     * Get total gross pay (all earnings)
     */
    public function getGrossPay(): float;
    
    /**
     * Get total employee deductions
     */
    public function getTotalDeductions(): float;
    
    /**
     * Get net pay (gross - deductions)
     */
    public function getNetPay(): float;
    
    /**
     * Get total employer contributions
     */
    public function getEmployerContributions(): float;
    
    /**
     * Get payslip status
     */
    public function getStatus(): PayslipStatus;
    
    /**
     * Get earnings breakdown (array of component amounts)
     */
    public function getEarningsBreakdown(): array;
    
    /**
     * Get deductions breakdown (array of component amounts)
     */
    public function getDeductionsBreakdown(): array;
    
    /**
     * Get payslip creation timestamp
     */
    public function getCreatedAt(): \DateTimeImmutable;
}
```

---

## Repository Interfaces (CQRS)

### ComponentQueryInterface

**Location:** `src/Contracts/ComponentQueryInterface.php`

**Purpose:** Read operations for payroll components.

```php
<?php

namespace Nexus\Payroll\Contracts;

interface ComponentQueryInterface
{
    /**
     * Find component by ID
     *
     * @param string $id Component ULID
     * @return ComponentInterface|null
     */
    public function findById(string $id): ?ComponentInterface;
    
    /**
     * Find component by code within tenant
     *
     * @param string $code Component code (e.g., 'BASIC')
     * @return ComponentInterface|null
     */
    public function findByCode(string $code): ?ComponentInterface;
    
    /**
     * Get all active components for tenant
     *
     * @return ComponentInterface[]
     */
    public function getActiveComponents(): array;
    
    /**
     * Get components by type
     *
     * @param ComponentType $type Component type filter
     * @return ComponentInterface[]
     */
    public function getByType(ComponentType $type): array;
}
```

---

### ComponentPersistInterface

**Location:** `src/Contracts/ComponentPersistInterface.php`

**Purpose:** Write operations for payroll components.

```php
<?php

namespace Nexus\Payroll\Contracts;

interface ComponentPersistInterface
{
    /**
     * Create a new component
     *
     * @param array<string, mixed> $data Component data
     * @return ComponentInterface Created component
     */
    public function create(array $data): ComponentInterface;
    
    /**
     * Update existing component
     *
     * @param string $id Component ULID
     * @param array<string, mixed> $data Updated data
     * @return ComponentInterface Updated component
     */
    public function update(string $id, array $data): ComponentInterface;
    
    /**
     * Delete component
     *
     * @param string $id Component ULID
     * @return bool True if deleted
     */
    public function delete(string $id): bool;
}
```

---

### EmployeeComponentQueryInterface

**Location:** `src/Contracts/EmployeeComponentQueryInterface.php`

**Purpose:** Read operations for employee component assignments.

```php
<?php

namespace Nexus\Payroll\Contracts;

interface EmployeeComponentQueryInterface
{
    /**
     * Find assignment by ID
     */
    public function findById(string $id): ?EmployeeComponentInterface;
    
    /**
     * Get active components for employee on a specific date
     *
     * @param string $employeeId Employee ULID
     * @param \DateTimeImmutable $date Date to check
     * @return EmployeeComponentInterface[]
     */
    public function getActiveComponentsForEmployee(
        string $employeeId,
        \DateTimeImmutable $date
    ): array;
    
    /**
     * Get all components for employee
     *
     * @param string $employeeId Employee ULID
     * @return EmployeeComponentInterface[]
     */
    public function getComponentsForEmployee(string $employeeId): array;
}
```

---

### EmployeeComponentPersistInterface

**Location:** `src/Contracts/EmployeeComponentPersistInterface.php`

**Purpose:** Write operations for employee component assignments.

```php
<?php

namespace Nexus\Payroll\Contracts;

interface EmployeeComponentPersistInterface
{
    /**
     * Create employee component assignment
     */
    public function create(array $data): EmployeeComponentInterface;
    
    /**
     * Update assignment
     */
    public function update(string $id, array $data): EmployeeComponentInterface;
    
    /**
     * Delete assignment
     */
    public function delete(string $id): bool;
}
```

---

### PayslipQueryInterface

**Location:** `src/Contracts/PayslipQueryInterface.php`

**Purpose:** Read operations for payslips.

```php
<?php

namespace Nexus\Payroll\Contracts;

interface PayslipQueryInterface
{
    /**
     * Find payslip by ID
     */
    public function findById(string $id): ?PayslipInterface;
    
    /**
     * Find payslip by number
     */
    public function findByPayslipNumber(string $number): ?PayslipInterface;
    
    /**
     * Get all payslips for employee
     *
     * @param string $employeeId Employee ULID
     * @return PayslipInterface[]
     */
    public function getEmployeePayslips(string $employeeId): array;
    
    /**
     * Get payslips for a period
     *
     * @param \DateTimeImmutable $periodStart Period start date
     * @param \DateTimeImmutable $periodEnd Period end date
     * @return PayslipInterface[]
     */
    public function getPayslipsForPeriod(
        \DateTimeImmutable $periodStart,
        \DateTimeImmutable $periodEnd
    ): array;
    
    /**
     * Get payslips by status
     *
     * @param PayslipStatus $status Status filter
     * @return PayslipInterface[]
     */
    public function getByStatus(PayslipStatus $status): array;
}
```

---

### PayslipPersistInterface

**Location:** `src/Contracts/PayslipPersistInterface.php`

**Purpose:** Write operations for payslips.

```php
<?php

namespace Nexus\Payroll\Contracts;

interface PayslipPersistInterface
{
    /**
     * Create payslip
     */
    public function create(array $data): PayslipInterface;
    
    /**
     * Update payslip
     */
    public function update(string $id, array $data): PayslipInterface;
    
    /**
     * Delete payslip
     */
    public function delete(string $id): bool;
}
```

---

## Statutory Calculation Interfaces

### StatutoryCalculatorInterface

**Location:** `src/Contracts/StatutoryCalculatorInterface.php`

**Purpose:** Contract for country-specific statutory calculation implementations.

```php
<?php

namespace Nexus\Payroll\Contracts;

interface StatutoryCalculatorInterface
{
    /**
     * Calculate statutory deductions for an employee
     *
     * @param PayloadInterface $payload Calculation input data
     * @return DeductionResultInterface Calculation results
     * @throws PayloadValidationException If payload is invalid
     */
    public function calculate(PayloadInterface $payload): DeductionResultInterface;
    
    /**
     * Get ISO country code supported by this calculator
     *
     * @return string ISO 3166-1 alpha-2 code (e.g., 'MY', 'SG')
     */
    public function getSupportedCountryCode(): string;
    
    /**
     * Get list of employee fields required for calculation
     *
     * @return string[] Array of required field names
     */
    public function getRequiredEmployeeFields(): array;
}
```

---

### PayloadInterface

**Location:** `src/Contracts/PayloadInterface.php`

**Purpose:** Input data structure for statutory calculations.

```php
<?php

namespace Nexus\Payroll\Contracts;

interface PayloadInterface
{
    /**
     * Get employee identifier
     */
    public function getEmployeeId(): string;
    
    /**
     * Get total gross pay for the period
     */
    public function getGrossPay(): float;
    
    /**
     * Get taxable income (gross minus non-taxable components)
     */
    public function getTaxableIncome(): float;
    
    /**
     * Get additional metadata for calculations
     * May include: age, marital_status, tax_status, epf_category, etc.
     *
     * @return array<string, mixed>
     */
    public function getMetadata(): array;
    
    /**
     * Get earnings breakdown by component
     *
     * @return array<string, float>
     */
    public function getEarningsBreakdown(): array;
    
    /**
     * Get Year-to-Date amounts for cumulative calculations
     *
     * @return array<string, float>
     */
    public function getYtdAmounts(): array;
}
```

---

### DeductionResultInterface

**Location:** `src/Contracts/DeductionResultInterface.php`

**Purpose:** Output structure from statutory calculations.

```php
<?php

namespace Nexus\Payroll\Contracts;

interface DeductionResultInterface
{
    /**
     * Get total employee deductions
     */
    public function getTotalDeductions(): float;
    
    /**
     * Get deduction breakdown by type
     *
     * Example:
     * [
     *     'EPF_EMPLOYEE' => 550.00,
     *     'SOCSO_EMPLOYEE' => 8.65,
     *     'EIS_EMPLOYEE' => 9.90,
     *     'PCB' => 125.00,
     * ]
     *
     * @return array<string, float>
     */
    public function getBreakdown(): array;
    
    /**
     * Get employer contributions by type
     *
     * Example:
     * [
     *     'EPF_EMPLOYER' => 650.00,
     *     'SOCSO_EMPLOYER' => 14.95,
     *     'EIS_EMPLOYER' => 9.90,
     * ]
     *
     * @return array<string, float>
     */
    public function getEmployerContributions(): array;
    
    /**
     * Get calculation audit data for compliance
     *
     * @return array<string, mixed>
     */
    public function getAuditData(): array;
}
```

---

## Service Classes

### PayrollEngine

**Location:** `src/Services/PayrollEngine.php`

**Purpose:** Main orchestration service for payroll processing.

```php
<?php

namespace Nexus\Payroll\Services;

final readonly class PayrollEngine
{
    public function __construct(
        private PayslipQueryInterface $payslipQuery,
        private PayslipPersistInterface $payslipPersist,
        private ComponentQueryInterface $componentQuery,
        private EmployeeComponentQueryInterface $employeeComponentQuery,
        private StatutoryCalculatorInterface $statutoryCalculator
    );
    
    /**
     * Process payroll for all employees in a period
     *
     * @param string $tenantId Tenant ULID
     * @param string $periodStart Period start date (Y-m-d)
     * @param string $periodEnd Period end date (Y-m-d)
     * @param array<string, mixed> $filters Optional filters (department_id, etc.)
     * @return PayslipInterface[] Generated payslips
     */
    public function processPeriod(
        string $tenantId,
        string $periodStart,
        string $periodEnd,
        array $filters = []
    ): array;
    
    /**
     * Process payroll for a single employee
     *
     * @param string $tenantId Tenant ULID
     * @param string $employeeId Employee ULID
     * @param string $periodStart Period start date (Y-m-d)
     * @param string $periodEnd Period end date (Y-m-d)
     * @return PayslipInterface Generated payslip
     */
    public function processEmployee(
        string $tenantId,
        string $employeeId,
        string $periodStart,
        string $periodEnd
    ): PayslipInterface;
}
```

---

### ComponentManager

**Location:** `src/Services/ComponentManager.php`

**Purpose:** Manages payroll component lifecycle.

```php
<?php

namespace Nexus\Payroll\Services;

final readonly class ComponentManager
{
    public function __construct(
        private ComponentQueryInterface $componentQuery,
        private ComponentPersistInterface $componentPersist
    );
    
    /**
     * Create a new payroll component
     *
     * @param array<string, mixed> $data Component data
     * @return ComponentInterface
     * @throws PayrollException If validation fails
     */
    public function createComponent(array $data): ComponentInterface;
    
    /**
     * Update existing component
     *
     * @param string $id Component ULID
     * @param array<string, mixed> $data Updated data
     * @return ComponentInterface
     * @throws ComponentNotFoundException
     */
    public function updateComponent(string $id, array $data): ComponentInterface;
    
    /**
     * Delete component
     *
     * @param string $id Component ULID
     * @return bool
     * @throws ComponentNotFoundException
     */
    public function deleteComponent(string $id): bool;
    
    /**
     * Get component by ID
     *
     * @param string $id Component ULID
     * @return ComponentInterface
     * @throws ComponentNotFoundException
     */
    public function getComponent(string $id): ComponentInterface;
    
    /**
     * Get all active components
     *
     * @return ComponentInterface[]
     */
    public function getActiveComponents(): array;
}
```

---

### PayslipManager

**Location:** `src/Services/PayslipManager.php`

**Purpose:** Manages payslip CRUD operations.

```php
<?php

namespace Nexus\Payroll\Services;

use Nexus\Payroll\ValueObjects\PayslipStatus;

final readonly class PayslipManager
{
    public function __construct(
        private PayslipQueryInterface $payslipQuery,
        private PayslipPersistInterface $payslipPersist
    );
    
    /**
     * Create a new payslip
     *
     * @param array<string, mixed> $data Payslip data
     * @return PayslipInterface
     * @throws PayslipValidationException
     */
    public function createPayslip(array $data): PayslipInterface;
    
    /**
     * Update payslip status
     *
     * @param string $id Payslip ULID
     * @param PayslipStatus $status New status
     * @return PayslipInterface
     * @throws PayslipNotFoundException
     */
    public function updatePayslipStatus(string $id, PayslipStatus $status): PayslipInterface;
    
    /**
     * Get payslip by ID
     *
     * @param string $id Payslip ULID
     * @return PayslipInterface
     * @throws PayslipNotFoundException
     */
    public function getPayslip(string $id): PayslipInterface;
    
    /**
     * Get payslips for employee
     *
     * @param string $employeeId Employee ULID
     * @return PayslipInterface[]
     */
    public function getEmployeePayslips(string $employeeId): array;
    
    /**
     * Get payslips for period
     *
     * @param \DateTimeImmutable $periodStart
     * @param \DateTimeImmutable $periodEnd
     * @return PayslipInterface[]
     */
    public function getPayslipsForPeriod(
        \DateTimeImmutable $periodStart,
        \DateTimeImmutable $periodEnd
    ): array;
}
```

---

## Value Objects / Enums

### ComponentType

**Location:** `src/ValueObjects/ComponentType.php`

**Purpose:** Categorizes payroll components.

```php
<?php

namespace Nexus\Payroll\ValueObjects;

enum ComponentType: string
{
    case EARNING = 'earning';
    case DEDUCTION = 'deduction';
    case EMPLOYER_CONTRIBUTION = 'employer_contribution';
}
```

**Usage:**
```php
use Nexus\Payroll\ValueObjects\ComponentType;

$type = ComponentType::EARNING;
echo $type->value; // 'earning'

// From database value
$type = ComponentType::from('deduction');
```

---

### CalculationMethod

**Location:** `src/ValueObjects/CalculationMethod.php`

**Purpose:** Defines how component amounts are calculated.

```php
<?php

namespace Nexus\Payroll\ValueObjects;

enum CalculationMethod: string
{
    case FIXED = 'fixed';
    case PERCENTAGE_OF_BASIC = 'percentage_of_basic';
    case PERCENTAGE_OF_GROSS = 'percentage_of_gross';
    case FORMULA = 'formula';
}
```

**Usage:**
```php
use Nexus\Payroll\ValueObjects\CalculationMethod;

$method = CalculationMethod::PERCENTAGE_OF_BASIC;
echo $method->value; // 'percentage_of_basic'
```

---

### PayslipStatus

**Location:** `src/ValueObjects/PayslipStatus.php`

**Purpose:** Represents payslip lifecycle states.

```php
<?php

namespace Nexus\Payroll\ValueObjects;

enum PayslipStatus: string
{
    case DRAFT = 'draft';
    case CALCULATED = 'calculated';
    case APPROVED = 'approved';
    case PAID = 'paid';
    case CANCELLED = 'cancelled';
}
```

**Status Transitions:**
```
DRAFT → CALCULATED → APPROVED → PAID
                  ↘          ↗
                    CANCELLED
```

---

## Exceptions

### PayrollException

**Location:** `src/Exceptions/PayrollException.php`

**Purpose:** Base exception for all payroll-related errors.

```php
<?php

namespace Nexus\Payroll\Exceptions;

class PayrollException extends \RuntimeException
{
}
```

---

### ComponentNotFoundException

**Location:** `src/Exceptions/ComponentNotFoundException.php`

**Purpose:** Thrown when a component is not found.

```php
<?php

namespace Nexus\Payroll\Exceptions;

class ComponentNotFoundException extends PayrollException
{
    public static function withId(string $id): self
    {
        return new self("Component with ID '{$id}' not found.");
    }
    
    public static function withCode(string $code): self
    {
        return new self("Component with code '{$code}' not found.");
    }
}
```

---

### PayslipNotFoundException

**Location:** `src/Exceptions/PayslipNotFoundException.php`

**Purpose:** Thrown when a payslip is not found.

```php
<?php

namespace Nexus\Payroll\Exceptions;

class PayslipNotFoundException extends PayrollException
{
    public static function withId(string $id): self
    {
        return new self("Payslip with ID '{$id}' not found.");
    }
}
```

---

### PayloadValidationException

**Location:** `src/Exceptions/PayloadValidationException.php`

**Purpose:** Thrown when statutory calculation payload is invalid.

```php
<?php

namespace Nexus\Payroll\Exceptions;

class PayloadValidationException extends PayrollException
{
    /**
     * @param string[] $missingFields
     */
    public static function missingFields(array $missingFields): self
    {
        return new self(sprintf(
            'Payload missing required fields: %s',
            implode(', ', $missingFields)
        ));
    }
    
    public static function invalidAmount(string $field, float $value): self
    {
        return new self(sprintf(
            "Invalid amount for field '%s': %.2f",
            $field,
            $value
        ));
    }
}
```

---

### PayslipValidationException

**Location:** `src/Exceptions/PayslipValidationException.php`

**Purpose:** Thrown when payslip data is invalid.

```php
<?php

namespace Nexus\Payroll\Exceptions;

class PayslipValidationException extends PayrollException
{
    public static function invalidPeriod(): self
    {
        return new self('Period end date must be after period start date.');
    }
    
    public static function invalidStatusTransition(string $from, string $to): self
    {
        return new self(sprintf(
            "Invalid status transition from '%s' to '%s'.",
            $from,
            $to
        ));
    }
}
```

---

## Usage Patterns

### Pattern 1: Basic Payroll Processing

```php
use Nexus\Payroll\Services\PayrollEngine;

$engine = app(PayrollEngine::class);

$payslips = $engine->processPeriod(
    tenantId: 'tenant-001',
    periodStart: '2025-01-01',
    periodEnd: '2025-01-31'
);

foreach ($payslips as $payslip) {
    // Process each payslip...
}
```

### Pattern 2: Component Setup

```php
use Nexus\Payroll\Services\ComponentManager;
use Nexus\Payroll\ValueObjects\ComponentType;
use Nexus\Payroll\ValueObjects\CalculationMethod;

$manager = app(ComponentManager::class);

// Create earnings component
$basic = $manager->createComponent([
    'tenant_id' => 'tenant-001',
    'code' => 'BASIC',
    'name' => 'Basic Salary',
    'type' => ComponentType::EARNING,
    'calculation_method' => CalculationMethod::FIXED,
    'is_taxable' => true,
    'is_statutory' => true,
]);

// Create percentage-based allowance
$housing = $manager->createComponent([
    'tenant_id' => 'tenant-001',
    'code' => 'HOUSING',
    'name' => 'Housing Allowance',
    'type' => ComponentType::EARNING,
    'calculation_method' => CalculationMethod::PERCENTAGE_OF_BASIC,
    'percentage' => 15.0,
    'is_taxable' => true,
    'is_statutory' => false,
]);
```

### Pattern 3: Payslip Status Workflow

```php
use Nexus\Payroll\Services\PayslipManager;
use Nexus\Payroll\ValueObjects\PayslipStatus;

$manager = app(PayslipManager::class);

// Get payslip
$payslip = $manager->getPayslip('payslip-001');

// Progress through workflow
$payslip = $manager->updatePayslipStatus($payslip->getId(), PayslipStatus::CALCULATED);
$payslip = $manager->updatePayslipStatus($payslip->getId(), PayslipStatus::APPROVED);
$payslip = $manager->updatePayslipStatus($payslip->getId(), PayslipStatus::PAID);
```

### Pattern 4: Error Handling

```php
use Nexus\Payroll\Services\ComponentManager;
use Nexus\Payroll\Exceptions\ComponentNotFoundException;

$manager = app(ComponentManager::class);

try {
    $component = $manager->getComponent('non-existent-id');
} catch (ComponentNotFoundException $e) {
    // Handle not found
    logger()->warning($e->getMessage());
    return response()->json(['error' => 'Component not found'], 404);
}
```
