# Getting Started with Nexus Leave

## Prerequisites

- PHP 8.3 or higher
- Composer
- Understanding of leave management concepts (accruals, balances, policies)

## Installation

```bash
composer require nexus/leave-management:"*@dev"
```

## When to Use This Package

This package is designed for:
- ✅ Managing employee leave applications and approvals
- ✅ Calculating leave balances with multiple accrual strategies
- ✅ Enforcing leave policies (balance limits, overlap detection)
- ✅ Supporting multiple leave categories (annual, sick, maternity, etc.)
- ✅ Handling country-specific statutory leave requirements
- ✅ Processing year-end carry-forward and encashment

Do NOT use this package for:
- ❌ Leave approval workflows (use `HumanResourceOperations` orchestrator)
- ❌ Leave calendar UI/notifications (application layer concern)
- ❌ Direct database operations (implement repository interfaces)
- ❌ Framework-specific features (package is framework-agnostic)

## Core Concepts

### Concept 1: Leave Categories

Leave is categorized by type, each with different accrual rules and policies:

```php
use Nexus\Leave\Enums\LeaveCategory;

// Standard categories
LeaveCategory::ANNUAL       // Vacation/annual leave
LeaveCategory::SICK         // Medical leave
LeaveCategory::MATERNITY    // Maternity leave
LeaveCategory::PATERNITY    // Paternity leave
LeaveCategory::UNPAID       // Leave without pay
LeaveCategory::COMPASSIONATE // Bereavement leave
LeaveCategory::STUDY        // Educational leave
LeaveCategory::OTHER        // Custom categories
```

### Concept 2: Leave Status Lifecycle

Every leave request flows through a status lifecycle:

```php
use Nexus\Leave\Enums\LeaveStatus;

// Status progression
LeaveStatus::DRAFT      // Initial creation
LeaveStatus::PENDING    // Awaiting approval
LeaveStatus::APPROVED   // Approved by manager
LeaveStatus::REJECTED   // Rejected by manager
LeaveStatus::CANCELLED  // Cancelled by employee
```

### Concept 3: Accrual Strategies

Different leave types use different accrual methods:

```php
use Nexus\Leave\Enums\AccrualFrequency;

AccrualFrequency::MONTHLY           // Accrued monthly (e.g., 1.17 days/month)
AccrualFrequency::QUARTERLY         // Accrued quarterly
AccrualFrequency::YEARLY            // Accrued at year start
AccrualFrequency::FIXED_ALLOCATION  // Fixed amount (e.g., maternity)
```

### Concept 4: Leave Duration Types

Leave can be taken in different durations:

```php
use Nexus\Leave\Enums\LeaveDuration;

LeaveDuration::FULL_DAY     // Complete day off
LeaveDuration::HALF_DAY_AM  // Morning half-day
LeaveDuration::HALF_DAY_PM  // Afternoon half-day
LeaveDuration::HOURS        // Hourly leave (where allowed)
```

## Basic Configuration

### Step 1: Implement Repository Interfaces

The package requires you to implement repository interfaces for data persistence:

```php
<?php

declare(strict_types=1);

namespace App\Repositories\Leave;

use Nexus\Leave\Contracts\LeaveRepositoryInterface;

final readonly class EloquentLeaveRepository implements LeaveRepositoryInterface
{
    public function __construct(
        private Leave $model
    ) {}

    public function findById(string $id): ?object
    {
        return $this->model->find($id);
    }

    public function findByEmployeeId(string $employeeId): array
    {
        return $this->model
            ->where('employee_id', $employeeId)
            ->get()
            ->all();
    }

    public function save(object $leave): string
    {
        $leave->save();
        return $leave->id;
    }

    public function delete(string $id): void
    {
        $this->model->findOrFail($id)->delete();
    }
}
```

### Step 2: Implement Balance Repository

```php
<?php

declare(strict_types=1);

namespace App\Repositories\Leave;

use Nexus\Leave\Contracts\LeaveBalanceRepositoryInterface;

final readonly class EloquentLeaveBalanceRepository implements LeaveBalanceRepositoryInterface
{
    public function __construct(
        private LeaveBalance $model
    ) {}

    public function findByEmployeeAndType(string $employeeId, string $leaveTypeId): ?object
    {
        return $this->model
            ->where('employee_id', $employeeId)
            ->where('leave_type_id', $leaveTypeId)
            ->first();
    }

    public function save(object $balance): string
    {
        $balance->save();
        return $balance->id;
    }

    public function updateBalance(string $balanceId, float $amount): void
    {
        $this->model->findOrFail($balanceId)->update([
            'balance' => $amount
        ]);
    }
}
```

### Step 3: Bind Interfaces in Service Provider

**Laravel:**
```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Nexus\Leave\Contracts\LeaveRepositoryInterface;
use Nexus\Leave\Contracts\LeaveBalanceRepositoryInterface;
use Nexus\Leave\Contracts\LeaveCalculatorInterface;
use Nexus\Leave\Services\LeaveBalanceCalculator;
use App\Repositories\Leave\EloquentLeaveRepository;
use App\Repositories\Leave\EloquentLeaveBalanceRepository;

class LeaveManagementServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind repositories
        $this->app->singleton(
            LeaveRepositoryInterface::class,
            EloquentLeaveRepository::class
        );

        $this->app->singleton(
            LeaveBalanceRepositoryInterface::class,
            EloquentLeaveBalanceRepository::class
        );

        // Bind calculator service
        $this->app->singleton(
            LeaveCalculatorInterface::class,
            LeaveBalanceCalculator::class
        );
    }
}
```

**Symfony (services.yaml):**
```yaml
services:
    Nexus\Leave\Contracts\LeaveRepositoryInterface:
        class: App\Repository\Leave\DoctrineLeaveRepository

    Nexus\Leave\Contracts\LeaveBalanceRepositoryInterface:
        class: App\Repository\Leave\DoctrineLeaveBalanceRepository

    Nexus\Leave\Contracts\LeaveCalculatorInterface:
        class: Nexus\Leave\Services\LeaveBalanceCalculator
        arguments:
            $balanceRepository: '@Nexus\Leave\Contracts\LeaveBalanceRepositoryInterface'
```

### Step 4: Use the Package

```php
<?php

declare(strict_types=1);

namespace App\Services;

use Nexus\Leave\Contracts\LeaveCalculatorInterface;
use Nexus\Leave\Contracts\LeaveRepositoryInterface;
use Nexus\Leave\Enums\LeaveStatus;
use Nexus\Leave\Exceptions\InsufficientBalanceException;

final readonly class LeaveApplicationService
{
    public function __construct(
        private LeaveCalculatorInterface $calculator,
        private LeaveRepositoryInterface $leaveRepository
    ) {}

    public function applyForLeave(
        string $employeeId,
        string $leaveTypeId,
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate,
        float $daysRequested
    ): string {
        // Check available balance
        $availableBalance = $this->calculator->calculateBalance(
            $employeeId,
            $leaveTypeId
        );

        if ($daysRequested > $availableBalance) {
            throw new InsufficientBalanceException(
                $employeeId,
                $leaveTypeId,
                $daysRequested,
                $availableBalance
            );
        }

        // Create leave request (using your entity/model)
        $leave = new \stdClass();
        $leave->employee_id = $employeeId;
        $leave->leave_type_id = $leaveTypeId;
        $leave->start_date = $startDate;
        $leave->end_date = $endDate;
        $leave->days = $daysRequested;
        $leave->status = LeaveStatus::PENDING->value;

        return $this->leaveRepository->save($leave);
    }
}
```

## Your First Integration

Here's a complete working example:

```php
<?php

use Nexus\Leave\Services\LeaveBalanceCalculator;
use Nexus\Leave\Contracts\LeaveBalanceRepositoryInterface;
use Nexus\Leave\Enums\LeaveCategory;
use Nexus\Leave\Enums\LeaveStatus;
use Nexus\Leave\Exceptions\InsufficientBalanceException;

// 1. Create mock repository for testing
$balanceRepository = new class implements LeaveBalanceRepositoryInterface {
    private array $balances = [];

    public function findByEmployeeAndType(string $employeeId, string $leaveTypeId): ?object
    {
        $key = "{$employeeId}:{$leaveTypeId}";
        return $this->balances[$key] ?? null;
    }

    public function save(object $balance): string
    {
        $id = $balance->id ?? uniqid();
        $key = "{$balance->employee_id}:{$balance->leave_type_id}";
        $this->balances[$key] = $balance;
        return $id;
    }

    public function updateBalance(string $balanceId, float $amount): void
    {
        // Implementation
    }
};

// 2. Initialize calculator
$calculator = new LeaveBalanceCalculator($balanceRepository);

// 3. Calculate balance for employee
$employeeId = 'emp-001';
$leaveTypeId = 'annual-leave';

$balance = $calculator->calculateBalance($employeeId, $leaveTypeId);
echo "Available balance: {$balance} days\n";

// 4. Check accrual
$accrual = $calculator->calculateAccrual(
    $employeeId,
    $leaveTypeId,
    new \DateTimeImmutable('2025-12-31')
);
echo "Year-to-date accrual: {$accrual} days\n";
```

## Next Steps

- Read the [API Reference](api-reference.md) for detailed interface documentation
- Check [Integration Guide](integration-guide.md) for framework-specific examples
- See [Examples](examples/) for more code samples

## Troubleshooting

### Common Issues

**Issue 1: Interface not bound**

**Error:**
```
Target interface [Nexus\Leave\Contracts\LeaveRepositoryInterface] is not instantiable.
```

**Cause:** The interface hasn't been bound to a concrete implementation.

**Solution:**
```php
// Laravel
$this->app->singleton(LeaveRepositoryInterface::class, YourImplementation::class);

// Symfony
services:
    Nexus\Leave\Contracts\LeaveRepositoryInterface:
        class: App\Repository\YourImplementation
```

**Issue 2: InsufficientBalanceException thrown**

**Error:**
```
Insufficient balance for employee emp-001, leave type annual. Requested: 5, Available: 3
```

**Cause:** Employee doesn't have enough leave balance.

**Solution:**
- Check employee's current balance before submitting request
- Display available balance in UI to prevent invalid requests
- Consider allowing negative balance for certain leave types if policy permits

**Issue 3: Wrong accrual calculation**

**Cause:** Using wrong accrual strategy for leave type.

**Solution:**
- Verify the correct `AccrualStrategyInterface` implementation is used
- Check leave type configuration for accrual frequency
- Verify employee's join date for proration calculations
