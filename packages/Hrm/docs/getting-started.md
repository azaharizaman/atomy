# Getting Started with Nexus Hrm

## Prerequisites

- PHP 8.3 or higher
- Composer
- Understanding of dependency injection
- Familiarity with repository pattern

## Installation

```bash
composer require nexus/hrm:"*@dev"
```

## When to Use This Package

This package is designed for:
- ✅ Employee master data management
- ✅ Employment contract tracking
- ✅ Leave entitlement and request management
- ✅ Attendance tracking (clock-in/out, breaks, overtime)
- ✅ Performance review management
- ✅ Disciplinary case management
- ✅ Training program enrollment and tracking
- ✅ HR compliance and audit trails

Do NOT use this package for:
- ❌ Payroll calculations (use `Nexus\Payroll` instead)
- ❌ Organizational structure (use `Nexus\Backoffice` instead)
- ❌ Workflow approvals (use `Nexus\Workflow` instead)
- ❌ Financial transactions (use `Nexus\Finance` instead)

## Core Concepts

### Concept 1: Framework-Agnostic Design

The Nexus\Hrm package contains ONLY business logic. It defines what needs to be done, not how to persist data. All database operations are abstracted through repository interfaces.

**Package Responsibilities:**
- Define entity interfaces (EmployeeInterface, LeaveInterface, etc.)
- Define repository contracts (EmployeeRepositoryInterface, etc.)
- Implement business logic (EmployeeManager, LeaveManager, etc.)
- Validate business rules (balance checks, overlap prevention)

**Your Application's Responsibilities:**
- Implement entity interfaces (Eloquent models, Doctrine entities)
- Implement repository interfaces (database queries)
- Create database migrations
- Bind interfaces to implementations in service container

### Concept 2: Employee Lifecycle Management

Employees transition through predefined lifecycle states:

```
Probationary → Confirmed → Resigned/Terminated/Retired
              ↓
           Suspended (temporary)
```

Each state transition triggers validation and business logic:
- **Probationary → Confirmed:** Validates probation completion date
- **Confirmed → Resigned:** Validates notice period compliance
- **Any → Terminated:** Calculates final settlement (unused leave encashment)

### Concept 3: Leave Management with Balance Tracking

Leave balances are tracked per employee, per leave type:
- **Entitlement:** Annual allocation based on contract and policy
- **Accrual:** Progressive accumulation over time
- **Deduction:** Approved leave requests reduce balance
- **Carry-forward:** Unused balance from previous year (policy-based)
- **Encashment:** Convert unused leave to cash on termination

**Balance Validation:**
```php
// Before approving leave, manager checks balance
if ($leaveRequest->days > $leaveBalance->available) {
    throw new LeaveBalanceValidationException("Insufficient balance");
}
```

### Concept 4: Attendance Tracking with Overlap Prevention

The package prevents duplicate or overlapping attendance records:
- **Clock-in:** Creates attendance record with timestamp
- **Clock-out:** Updates record, calculates hours worked
- **Overlap Detection:** Prevents clock-in if existing record is not clocked out
- **Break Tracking:** Deducts break time from total hours
- **Overtime Calculation:** Applies threshold rules (e.g., >8 hours = overtime)

### Concept 5: Integration with Nexus\Backoffice

The package integrates with `Nexus\Backoffice` via `OrganizationServiceContract` to fetch:
- Employee's direct manager
- Employee's department and office
- Direct reports for managers

**Example:**
```php
// EmployeeManager fetches manager from Backoffice
$managerId = $this->organizationService->getEmployeeManager($employeeId);
```

This eliminates data duplication—organizational structure is owned by Backoffice, HR uses it via interface.

## Basic Configuration

### Step 1: Implement Required Interfaces

You must implement ALL entity and repository interfaces for the package to function.

#### Example: Employee Entity (Laravel Eloquent)

```php
namespace App\Models\Hrm;

use Illuminate\Database\Eloquent\Model;
use Nexus\Hrm\Contracts\EmployeeInterface;
use Nexus\Hrm\ValueObjects\EmployeeStatus;
use Nexus\Hrm\ValueObjects\EmploymentType;

class Employee extends Model implements EmployeeInterface
{
    protected $fillable = [
        'id', 'tenant_id', 'employee_code', 'first_name', 'last_name',
        'email', 'phone', 'date_of_birth', 'hire_date', 'termination_date',
        'employment_type', 'status', 'manager_id', 'department_id', 'office_id'
    ];

    protected $casts = [
        'employment_type' => EmploymentType::class,
        'status' => EmployeeStatus::class,
        'date_of_birth' => 'date',
        'hire_date' => 'date',
        'termination_date' => 'date',
    ];

    public function getId(): string
    {
        return $this->id;
    }

    public function getTenantId(): string
    {
        return $this->tenant_id;
    }

    public function getEmployeeCode(): string
    {
        return $this->employee_code;
    }

    public function getFirstName(): string
    {
        return $this->first_name;
    }

    public function getLastName(): string
    {
        return $this->last_name;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function getDateOfBirth(): ?\DateTimeImmutable
    {
        return $this->date_of_birth ? \DateTimeImmutable::createFromMutable($this->date_of_birth) : null;
    }

    public function getHireDate(): \DateTimeImmutable
    {
        return \DateTimeImmutable::createFromMutable($this->hire_date);
    }

    public function getTerminationDate(): ?\DateTimeImmutable
    {
        return $this->termination_date ? \DateTimeImmutable::createFromMutable($this->termination_date) : null;
    }

    public function getEmploymentType(): EmploymentType
    {
        return $this->employment_type;
    }

    public function getStatus(): EmployeeStatus
    {
        return $this->status;
    }

    public function getManagerId(): ?string
    {
        return $this->manager_id;
    }

    public function getDepartmentId(): ?string
    {
        return $this->department_id;
    }

    public function getOfficeId(): ?string
    {
        return $this->office_id;
    }
}
```

#### Example: Employee Repository (Laravel)

```php
namespace App\Repositories\Hrm;

use App\Models\Hrm\Employee;
use Nexus\Hrm\Contracts\EmployeeInterface;
use Nexus\Hrm\Contracts\EmployeeRepositoryInterface;
use Nexus\Hrm\Exceptions\EmployeeNotFoundException;
use Nexus\Hrm\Exceptions\EmployeeDuplicateException;
use Nexus\Tenant\Contracts\TenantContextInterface;

final readonly class EloquentEmployeeRepository implements EmployeeRepositoryInterface
{
    public function __construct(
        private TenantContextInterface $tenantContext
    ) {}

    public function findById(string $id): EmployeeInterface
    {
        $employee = Employee::where('tenant_id', $this->tenantContext->getCurrentTenantId())
            ->find($id);

        if (!$employee) {
            throw EmployeeNotFoundException::withId($id);
        }

        return $employee;
    }

    public function findByEmployeeCode(string $code): ?EmployeeInterface
    {
        return Employee::where('tenant_id', $this->tenantContext->getCurrentTenantId())
            ->where('employee_code', $code)
            ->first();
    }

    public function findByEmail(string $email): ?EmployeeInterface
    {
        return Employee::where('tenant_id', $this->tenantContext->getCurrentTenantId())
            ->where('email', $email)
            ->first();
    }

    public function save(EmployeeInterface $employee): void
    {
        if ($employee instanceof Employee) {
            $employee->save();
        }
    }

    public function delete(string $id): void
    {
        Employee::where('tenant_id', $this->tenantContext->getCurrentTenantId())
            ->where('id', $id)
            ->delete();
    }

    public function findAll(array $filters = []): array
    {
        $query = Employee::where('tenant_id', $this->tenantContext->getCurrentTenantId());

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }

        return $query->get()->all();
    }
}
```

### Step 2: Bind Interfaces in Service Provider

#### Laravel Service Provider

```php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Nexus\Hrm\Contracts\EmployeeRepositoryInterface;
use Nexus\Hrm\Contracts\LeaveRepositoryInterface;
use Nexus\Hrm\Contracts\AttendanceRepositoryInterface;
use App\Repositories\Hrm\EloquentEmployeeRepository;
use App\Repositories\Hrm\EloquentLeaveRepository;
use App\Repositories\Hrm\EloquentAttendanceRepository;

class HrmServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind repositories
        $this->app->singleton(
            EmployeeRepositoryInterface::class,
            EloquentEmployeeRepository::class
        );

        $this->app->singleton(
            LeaveRepositoryInterface::class,
            EloquentLeaveRepository::class
        );

        $this->app->singleton(
            AttendanceRepositoryInterface::class,
            EloquentAttendanceRepository::class
        );

        // Managers are auto-resolved (no binding needed if no custom implementation)
    }
}
```

Add to `config/app.php`:

```php
'providers' => [
    // ...
    App\Providers\HrmServiceProvider::class,
],
```

### Step 3: Create Database Migrations

**Migration: employees table**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->string('id', 26)->primary(); // ULID
            $table->string('tenant_id', 26)->index();
            $table->string('employee_code', 50)->unique();
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('email', 255)->unique();
            $table->string('phone', 50)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->date('hire_date');
            $table->date('termination_date')->nullable();
            $table->string('employment_type', 50); // Enum: full_time, part_time, contract, etc.
            $table->string('status', 50); // Enum: probationary, confirmed, resigned, etc.
            $table->string('manager_id', 26)->nullable();
            $table->string('department_id', 26)->nullable();
            $table->string('office_id', 26)->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'department_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
```

**Migration: leave_requests table**

```php
Schema::create('leave_requests', function (Blueprint $table) {
    $table->string('id', 26)->primary();
    $table->string('tenant_id', 26)->index();
    $table->string('employee_id', 26);
    $table->string('leave_type_id', 26);
    $table->date('start_date');
    $table->date('end_date');
    $table->decimal('days_requested', 8, 2);
    $table->text('reason')->nullable();
    $table->string('status', 50); // pending, approved, rejected, cancelled
    $table->string('approved_by', 26)->nullable();
    $table->timestamp('approved_at')->nullable();
    $table->timestamps();

    $table->foreign('employee_id')->references('id')->on('employees');
    $table->index(['tenant_id', 'employee_id', 'status']);
});
```

### Step 4: Use the Package

#### Create Employee

```php
use Nexus\Hrm\Services\EmployeeManager;
use Nexus\Hrm\ValueObjects\EmploymentType;

final readonly class EmployeeController
{
    public function __construct(
        private EmployeeManager $employeeManager
    ) {}

    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_code' => 'required|string|unique:employees',
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'required|email|unique:employees',
            'date_of_birth' => 'required|date',
            'hire_date' => 'required|date',
            'employment_type' => 'required|string',
        ]);

        $employee = $this->employeeManager->createEmployee([
            'employee_code' => $validated['employee_code'],
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'date_of_birth' => $validated['date_of_birth'],
            'hire_date' => $validated['hire_date'],
            'employment_type' => EmploymentType::from($validated['employment_type']),
        ]);

        return response()->json($employee, 201);
    }
}
```

#### Request Leave

```php
use Nexus\Hrm\Services\LeaveManager;

public function requestLeave(Request $request)
{
    $validated = $request->validate([
        'employee_id' => 'required|string',
        'leave_type_id' => 'required|string',
        'start_date' => 'required|date',
        'end_date' => 'required|date|after_or_equal:start_date',
        'reason' => 'nullable|string',
    ]);

    $leaveRequest = $this->leaveManager->createLeaveRequest([
        'employee_id' => $validated['employee_id'],
        'leave_type_id' => $validated['leave_type_id'],
        'start_date' => $validated['start_date'],
        'end_date' => $validated['end_date'],
        'reason' => $validated['reason'] ?? '',
    ]);

    return response()->json($leaveRequest, 201);
}
```

#### Track Attendance

```php
use Nexus\Hrm\Services\AttendanceManager;

public function clockIn(Request $request)
{
    $validated = $request->validate([
        'employee_id' => 'required|string',
        'location' => 'nullable|string',
        'latitude' => 'nullable|numeric',
        'longitude' => 'nullable|numeric',
    ]);

    $attendance = $this->attendanceManager->clockIn(
        $validated['employee_id'],
        [
            'location' => $validated['location'] ?? null,
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
        ]
    );

    return response()->json($attendance, 201);
}

public function clockOut(string $attendanceId)
{
    $this->attendanceManager->clockOut($attendanceId);

    return response()->json(['message' => 'Clocked out successfully']);
}
```

## Your First Integration

Here's a complete example showing employee onboarding with contract and leave entitlement:

```php
use Nexus\Hrm\Services\EmployeeManager;
use Nexus\Hrm\Services\LeaveManager;
use Nexus\Hrm\ValueObjects\EmploymentType;
use Nexus\Hrm\ValueObjects\ContractType;

// 1. Create employee
$employee = $employeeManager->createEmployee([
    'employee_code' => 'EMP001',
    'first_name' => 'Jane',
    'last_name' => 'Smith',
    'email' => 'jane.smith@company.com',
    'date_of_birth' => '1992-05-15',
    'hire_date' => '2025-01-01',
    'employment_type' => EmploymentType::full_time,
]);

// 2. Create employment contract
$contract = $contractManager->createContract([
    'employee_id' => $employee->getId(),
    'contract_type' => ContractType::permanent,
    'start_date' => '2025-01-01',
    'probation_months' => 3,
    'position_title' => 'Software Engineer',
    'salary' => 80000.00,
]);

// 3. Set up leave entitlements
$leaveManager->setupLeaveEntitlements($employee->getId(), [
    'annual_leave' => 14, // days per year
    'medical_leave' => 14,
    'emergency_leave' => 5,
]);

// 4. After probation (3 months later)
$employeeManager->confirmEmployee($employee->getId(), '2025-04-01');

echo "Employee {$employee->getEmployeeCode()} onboarded successfully!";
```

## Next Steps

- Read the [API Reference](api-reference.md) for detailed interface documentation
- Check [Integration Guide](integration-guide.md) for Laravel and Symfony examples
- See [Examples](examples/) for more code samples
- Review `REQUIREMENTS.md` for complete business rules
- Check `IMPLEMENTATION_SUMMARY.md` for package capabilities

## Troubleshooting

### Common Issues

**Issue 1: Interface not bound**

**Error:**
```
Target interface [Nexus\Hrm\Contracts\EmployeeRepositoryInterface] is not instantiable.
```

**Solution:**
Ensure you've created a service provider and bound all repository interfaces:

```php
$this->app->singleton(
    EmployeeRepositoryInterface::class,
    EloquentEmployeeRepository::class
);
```

**Issue 2: Leave balance validation exception**

**Error:**
```
LeaveBalanceValidationException: Insufficient leave balance
```

**Solution:**
Check employee's leave balance before creating request. Ensure leave entitlements are set up:

```php
$balance = $leaveManager->getLeaveBalance($employeeId, $leaveTypeId);
if ($balance->getAvailable() < $daysRequested) {
    // Handle insufficient balance
}
```

**Issue 3: Attendance overlap exception**

**Error:**
```
AttendanceValidationException: Employee already has an active attendance record
```

**Solution:**
Ensure employee clocks out before attempting another clock-in:

```php
// Check if employee has active attendance
$activeAttendance = $attendanceRepository->findActiveByEmployee($employeeId);
if ($activeAttendance) {
    // Remind user to clock out first
}
```

**Issue 4: Tenant context missing**

**Error:**
```
Call to a member function getCurrentTenantId() on null
```

**Solution:**
Ensure `Nexus\Tenant` package is installed and tenant middleware is active. Bind `TenantContextInterface` in your service provider.

## Integration with Other Nexus Packages

### Required Dependencies

- **Nexus\Backoffice** - Organizational structure (manager hierarchy, departments)
- **Nexus\Workflow** - Leave approval workflows, performance review workflows
- **Nexus\AuditLogger** - Track employee lifecycle changes, leave approvals

### Optional Integrations

- **Nexus\Payroll** - Attendance data for payroll calculations
- **Nexus\Notifier** - Email notifications for leave approvals, attendance reminders
- **Nexus\Monitoring** - Track HR metrics (turnover rate, leave utilization)

## Performance Considerations

### Indexing Strategy

Always index these columns for optimal query performance:

```sql
-- Employees table
INDEX idx_tenant_status (tenant_id, status)
INDEX idx_tenant_department (tenant_id, department_id)
INDEX idx_employee_code (employee_code)
INDEX idx_email (email)

-- Leave requests table
INDEX idx_tenant_employee_status (tenant_id, employee_id, status)
INDEX idx_leave_dates (start_date, end_date)

-- Attendance records table
INDEX idx_tenant_employee_date (tenant_id, employee_id, clock_in_time)
```

### Caching Recommendations

Cache frequently accessed data:

```php
// Cache leave balances (invalidate on leave approval/rejection)
$balance = Cache::remember(
    "leave_balance.{$employeeId}.{$leaveTypeId}",
    3600,
    fn() => $leaveManager->getLeaveBalance($employeeId, $leaveTypeId)
);

// Cache employee details (invalidate on update)
$employee = Cache::remember(
    "employee.{$employeeId}",
    3600,
    fn() => $employeeRepository->findById($employeeId)
);
```

## Security Best Practices

1. **Always validate input** before passing to managers
2. **Check authorization** before allowing operations (use `Nexus\Identity`)
3. **Audit sensitive operations** (termination, salary changes)
4. **Encrypt sensitive fields** (identification numbers, personal data)
5. **Implement RBAC** for HR operations (only HR admins can terminate employees)

---

**Documentation Version:** 1.0.0  
**Last Updated:** 2025-11-25  
**Package Version:** 1.0.0
