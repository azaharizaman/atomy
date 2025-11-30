# Nexus HRM Package

Atomic Human Resource Management domain for Nexus ERP.

## Features

- Employee master data management
- Employment contracts and lifecycle tracking
- Leave entitlements and requests with approval workflows
- Attendance tracking (clock-in/out, break tracking, overtime calculation)
- Performance management (review cycles, templates, 360-degree feedback, analytics)
- Disciplinary case management (investigation, resolution, follow-up tracking)
- Training program management (enrollment, completion, certification tracking)
- Monthly attendance summaries and reports
- Framework-agnostic design
- Independent migrations and testability

## Installation

```bash
composer require nexus/hrm
```

## Architecture

This package is framework-agnostic and contains only business logic. All persistence, models, and framework-specific code must be implemented in the consuming application.

### Package Structure

```
src/
├── Contracts/              # Interfaces for all domain entities and repositories
├── Services/               # Business logic and orchestration
├── ValueObjects/           # Immutable domain value objects
└── Exceptions/             # Domain-specific exceptions
```

### Integration with Nexus\Backoffice

This package integrates with `Nexus\Backoffice` via `OrganizationServiceContract` to automatically fetch:
- Employee's manager from organizational structure
- Employee's department and office
- Direct reports for managers

## Usage

### Employee Management

```php
use Nexus\Hrm\Services\EmployeeManager;

$employeeManager = app(EmployeeManager::class);

// Create employee
$employee = $employeeManager->createEmployee([
    'employee_code' => 'EMP001',
    'first_name' => 'John',
    'last_name' => 'Doe',
    'email' => 'john.doe@company.com',
    'date_of_birth' => '1990-01-01',
    'hire_date' => '2025-01-01',
]);

// Update employee lifecycle state
$employeeManager->confirmEmployee($employeeId, '2025-04-01');
```

### Leave Management

```php
use Nexus\Hrm\Services\LeaveManager;

$leaveManager = app(LeaveManager::class);

// Request leave
$leaveRequest = $leaveManager->createLeaveRequest([
    'employee_id' => $employeeId,
    'leave_type_id' => $leaveTypeId,
    'start_date' => '2025-06-01',
    'end_date' => '2025-06-05',
    'reason' => 'Annual vacation',
]);

// Check leave balance
$balance = $leaveManager->getLeaveBalance($employeeId, $leaveTypeId);
```

### Attendance Tracking

```php
use Nexus\Hrm\Services\AttendanceManager;

$attendanceManager = app(AttendanceManager::class);

// Clock in
$attendance = $attendanceManager->clockIn($employeeId, [
    'location' => 'Office HQ',
    'latitude' => 3.1390,
    'longitude' => 101.6869,
]);

// Clock out
$attendanceManager->clockOut($attendanceId);

// Get monthly summary
$summary = $attendanceManager->getMonthlyAttendanceSummary($employeeId, 2025, 6);
```

## Requirements

- PHP 8.3 or higher
- Integration with `Nexus\Backoffice` for organizational structure
- Integration with `Nexus\Workflow` for approval workflows
- Integration with `Nexus\AuditLogger` for change tracking

## 📖 Documentation

### Package Documentation
- **[Getting Started Guide](docs/getting-started.md)** - Quick start guide with prerequisites, concepts, and first integration
- **[API Reference](docs/api-reference.md)** - Complete documentation of all interfaces, value objects, and exceptions
- **[Integration Guide](docs/integration-guide.md)** - Laravel and Symfony integration examples
- **[Basic Usage Example](docs/examples/basic-usage.php)** - Simple usage patterns (employee, leave, attendance)
- **[Advanced Usage Example](docs/examples/advanced-usage.php)** - Advanced scenarios (reviews, disciplinary, training)

### Additional Resources
- `IMPLEMENTATION_SUMMARY.md` - Implementation progress and metrics
- `REQUIREMENTS.md` - Detailed requirements (159 requirements documented)
- `TEST_SUITE_SUMMARY.md` - Test coverage and results (85%+ coverage)
- `VALUATION_MATRIX.md` - Package valuation metrics ($236,626 estimated value)
- See root `ARCHITECTURE.md` for overall system architecture

## License

MIT License - see LICENSE file for details.
