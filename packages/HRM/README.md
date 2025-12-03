# Nexus HRM Package Suite

**Complete HR Management Solution - Atomic Packages + Orchestrator**

This directory contains the complete HRM domain implementation for the Nexus ERP system, following Clean Architecture and Domain-Driven Design principles.

## 📦 Package Overview

### **Orchestration Layer**
- **[HumanResourceOperations](HumanResourceOperations/)** - Coordinates all HR workflows and use cases

### **Atomic Domain Packages**
1. **[LeaveManagement](LeaveManagement/)** - Leave application, balances, accrual strategies
2. **[AttendanceManagement](AttendanceManagement/)** - Check-in/out, schedules, anomaly detection
3. **[PayrollCore](PayrollCore/)** - Payslip generation, calculations, adjustments
4. **[EmployeeProfile](EmployeeProfile/)** - Employee lifecycle, contracts, positions
5. **[ShiftManagement](ShiftManagement/)** - Shift scheduling, templates, assignments

## 🏗️ Architecture

```
packages/HRM/
├── HumanResourceOperations/    # Orchestrator (depends on all atomic packages)
├── LeaveManagement/            # Pure domain logic
├── AttendanceManagement/       # Pure domain logic
├── PayrollCore/                # Pure domain logic
├── EmployeeProfile/            # Pure domain logic
└── ShiftManagement/            # Pure domain logic
```

### Key Principles

✅ **Framework Agnostic** - Pure PHP 8.3+ (no Laravel/Symfony dependencies)  
✅ **Contract-Driven** - All dependencies via interfaces  
✅ **Stateless** - No in-memory state persistence  
✅ **Publishable** - Each package independently publishable to Packagist  
✅ **Testable** - Full unit test coverage with mocked dependencies  

## 🔗 Dependencies

### Orchestrator Dependencies
```json
{
    "nexus/leave-management": "*@dev",
    "nexus/attendance-management": "*@dev",
    "nexus/payroll-core": "*@dev",
    "nexus/employee-profile": "*@dev",
    "nexus/shift-management": "*@dev"
}
```

### Atomic Packages
Each atomic package has **zero internal dependencies** - they are completely independent.

## 🚀 Quick Start

### Install All Packages
```bash
composer require nexus/human-resource-operations
```

This will automatically install all atomic dependencies.

### Install Individual Packages
```bash
composer require nexus/leave-management
composer require nexus/attendance-management
composer require nexus/payroll-core
composer require nexus/employee-profile
composer require nexus/shift-management
```

## 📖 Usage Examples

### Leave Application (via Orchestrator)
```php
use Nexus\HumanResourceOperations\UseCases\Leave\ApplyLeaveHandler;

$handler = new ApplyLeaveHandler($leaveRepo, $balanceRepo, $policy, $logger);
$leaveId = $handler->handle('employee-123', [
    'leave_type_id' => 'annual',
    'start_date' => '2025-01-15',
    'end_date' => '2025-01-17',
    'reason' => 'Family vacation',
]);
```

### Direct Domain Usage (Leave Management)
```php
use Nexus\LeaveManagement\Services\LeaveBalanceCalculator;

$calculator = new LeaveBalanceCalculator($balanceRepo);
$balance = $calculator->calculateBalance('employee-123', 'annual');
```

## 📚 Documentation

- [Architecture Overview](HumanResourceOperations/docs/architecture-overview.md)
- Package-specific READMEs in each package directory

## 🧪 Testing

Each package includes its own test suite:

```bash
# Test orchestrator
cd HumanResourceOperations && vendor/bin/phpunit

# Test atomic packages
cd LeaveManagement && vendor/bin/phpunit
cd AttendanceManagement && vendor/bin/phpunit
```

## 🤝 Contributing

Follow Nexus coding guidelines:
- Pure PHP 8.3+ (no framework code in domain packages)
- All dependencies via constructor injection
- Readonly properties
- Complete type hints
- Native PHP enums
- Comprehensive PHPDoc

## 📄 License

MIT License - see individual package LICENSE files

## 🔄 Version

This is the refactored HRM structure (v2.0) - migrated from legacy monolithic design to modular atomic packages.
