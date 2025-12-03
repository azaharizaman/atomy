# HRM Package Quick Reference Guide

## 🎯 Purpose of Each Package

### HumanResourceOperations (Orchestrator)
**Use when:** You need to coordinate workflows across multiple HR domains  
**Examples:** Employee onboarding (uses Employee + Leave + Payroll), Payroll processing (uses Attendance + Leave + Payroll)

### LeaveManagement
**Use when:** Managing leave balances, applications, approvals, accrual  
**Examples:** Calculate annual leave balance, apply for sick leave, process year-end carry-forward

### AttendanceManagement  
**Use when:** Recording attendance, managing work schedules  
**Examples:** Clock in/out, detect late arrivals, validate attendance against schedule

### PayrollCore
**Use when:** Calculating payroll, generating payslips  
**Examples:** Calculate monthly salary, generate payslip, process overtime pay

### EmployeeProfile
**Use when:** Managing employee master data  
**Examples:** Create employee record, update contract, track employment history

### ShiftManagement
**Use when:** Scheduling shifts, managing shift patterns  
**Examples:** Create rotating shifts, assign shift to employee, detect shift overlaps

---

## 📋 Common Tasks

### Task: Apply for Leave

**Option 1: Via Orchestrator (Recommended)**
```php
use Nexus\HumanResourceOperations\UseCases\Leave\ApplyLeaveHandler;

$handler = new ApplyLeaveHandler($leaveRepo, $balanceRepo, $policy, $logger);
$leaveId = $handler->handle('employee-123', [
    'leave_type_id' => 'annual',
    'start_date' => '2025-01-15',
    'end_date' => '2025-01-17',
]);
```

**Option 2: Direct Domain Usage**
```php
use Nexus\LeaveManagement\Services\LeaveBalanceCalculator;

$calculator = new LeaveBalanceCalculator($balanceRepo);
$balance = $calculator->calculateBalance('employee-123', 'annual');
```

### Task: Record Attendance

```php
use Nexus\HumanResourceOperations\UseCases\Attendance\RecordCheckInHandler;

$handler = new RecordCheckInHandler($attendanceRepo, $scheduleRepo, $logger);
$handler->handle('employee-123', new \DateTimeImmutable());
```

### Task: Generate Payslip

```php
use Nexus\HumanResourceOperations\UseCases\Payroll\GeneratePayslipHandler;

$handler = new GeneratePayslipHandler($payrollService, $attendanceRepo, $leaveRepo);
$payslipId = $handler->handle('employee-123', '2025-01');
```

---

## 🔌 Integration Points

### Laravel Integration (Future)

Each atomic package will have a Laravel adapter:

```
adapters/Laravel/
├── LeaveManagement/
│   ├── Models/Leave.php (Eloquent)
│   ├── Repositories/EloquentLeaveRepository.php
│   └── Migrations/
├── AttendanceManagement/
├── PayrollCore/
├── EmployeeProfile/
└── ShiftManagement/
```

Service Provider binding:
```php
// App\Providers\HRServiceProvider
$this->app->bind(
    LeaveRepositoryInterface::class,
    EloquentLeaveRepository::class
);
```

---

## 🧪 Testing Examples

### Unit Test (Atomic Package)
```php
use Nexus\LeaveManagement\Services\LeaveBalanceCalculator;
use PHPUnit\Framework\TestCase;

class LeaveBalanceCalculatorTest extends TestCase
{
    public function test_calculates_balance_correctly(): void
    {
        $mockRepo = $this->createMock(LeaveBalanceRepositoryInterface::class);
        $calculator = new LeaveBalanceCalculator($mockRepo);
        
        // Test logic...
    }
}
```

### Integration Test (Orchestrator)
```php
use Nexus\HumanResourceOperations\UseCases\Leave\ApplyLeaveHandler;

class ApplyLeaveHandlerTest extends TestCase
{
    public function test_applies_leave_successfully(): void
    {
        // Mock all dependencies
        // Test orchestration logic
    }
}
```

---

## 📦 Composer Usage

### Install Everything
```bash
composer require nexus/human-resource-operations
```

### Install Individual Packages
```bash
composer require nexus/leave-management
composer require nexus/attendance-management
composer require nexus/payroll-core
composer require nexus/employee-profile
composer require nexus/shift-management
```

### Development (Monorepo)
```json
{
    "repositories": [
        {"type": "path", "url": "./packages/HRM/LeaveManagement"},
        {"type": "path", "url": "./packages/HRM/AttendanceManagement"},
        {"type": "path", "url": "./packages/HRM/PayrollCore"},
        {"type": "path", "url": "./packages/HRM/EmployeeProfile"},
        {"type": "path", "url": "./packages/HRM/ShiftManagement"},
        {"type": "path", "url": "./packages/HRM/HumanResourceOperations"}
    ]
}
```

---

## 🚦 Decision Matrix: Which Package to Use?

| I need to... | Use Package | Layer |
|-------------|-------------|-------|
| Coordinate multi-domain workflows | HumanResourceOperations | Orchestrator |
| Calculate leave balance | LeaveManagement | Atomic |
| Apply for leave (full workflow) | HumanResourceOperations | Orchestrator |
| Record check-in/out | AttendanceManagement | Atomic |
| Generate payslip | PayrollCore or HumanResourceOperations | Atomic or Orchestrator |
| Create employee | EmployeeProfile | Atomic |
| Schedule shifts | ShiftManagement | Atomic |

**Rule of Thumb:**
- **Atomic Package:** Single-domain operations, pure business logic
- **Orchestrator:** Multi-domain workflows, cross-package coordination

---

## 🔧 Development Workflow

1. **Define Domain Logic** in atomic packages (Contracts, Services, Entities)
2. **Implement Use Cases** in orchestrator (coordinate atomic packages)
3. **Create Framework Adapters** in adapters/ (Eloquent, migrations, controllers)
4. **Wire Dependencies** in application service providers
5. **Test** each layer independently

---

## 📚 Key Files to Review

### Architecture
- `packages/HRM/README.md` - Overview
- `packages/HRM/HumanResourceOperations/docs/architecture-overview.md` - Detailed architecture
- `packages/HRM/STRUCTURE_CREATION_SUMMARY.md` - Implementation status

### Package Documentation
- Each package has its own README.md with usage examples
- Check composer.json for dependencies

---

**Last Updated:** December 3, 2025
