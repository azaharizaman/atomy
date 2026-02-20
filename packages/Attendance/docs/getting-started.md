# Getting Started with Nexus Attendance

**Package:** `nexus/attendance-management`  
**Namespace:** `Nexus\Attendance`  
**Version:** 1.0.0  
**PHP:** 8.3+

---

## Table of Contents

1. [Prerequisites](#prerequisites)
2. [Installation](#installation)
3. [Core Concepts](#core-concepts)
4. [Basic Configuration](#basic-configuration)
5. [Your First Integration](#your-first-integration)
6. [Common Patterns](#common-patterns)
7. [Next Steps](#next-steps)
8. [Troubleshooting](#troubleshooting)

---

## Prerequisites

Before you begin, ensure you have:

### Required
- **PHP 8.3 or higher** (for `readonly` modifier, native enums, and modern features)
- **Composer** (for package installation)
- **PSR-3 Logger implementation** (e.g., `monolog/monolog`)

### Recommended
- **Laravel 10+** or **Symfony 6+** (framework adapter examples provided)
- **Database** (MySQL 8+, PostgreSQL 13+, or SQLite 3.35+)
- **Redis/Memcached** (optional, for caching work schedules)

### Knowledge Prerequisites
- Basic understanding of **Clean Architecture** and **DDD** concepts
- Familiarity with **CQRS pattern** (Command Query Responsibility Segregation)
- Understanding of **dependency injection** and **interfaces**

---

## Installation

### Step 1: Install Package via Composer

```bash
composer require nexus/attendance-management
```

### Step 2: Verify Installation

```bash
composer show nexus/attendance-management
```

You should see package information including version and dependencies.

### Step 3: Install Framework Adapter (Optional)

If using Laravel:

```bash
composer require nexus/attendance-laravel-adapter
```

If using Symfony:

```bash
composer require nexus/attendance-symfony-adapter
```

**Note:** The core package is framework-agnostic. Adapters provide concrete implementations (Eloquent models, Doctrine entities, migrations).

---

## Core Concepts

Understanding these concepts is essential before integrating the Attendance package.

### 1. **Attendance Records**

An **Attendance Record** represents a single attendance entry with:
- **Check-in timestamp** (when employee arrived)
- **Check-out timestamp** (when employee left, optional until checked out)
- **GPS coordinates** (optional, for location tracking)
- **Work hours** (calculated duration between check-in and check-out)
- **Notes** (optional comments)

**Key Rules:**
- Employees cannot check in twice without checking out first
- Check-out must be chronologically after check-in
- Records are **immutable** after creation (use "with*" methods for modifications)

**Example Domain Object:**
```php
use Nexus\Attendance\Domain\Entities\AttendanceRecord;
use Nexus\Attendance\Domain\ValueObjects\AttendanceId;

$record = new AttendanceRecord(
    id: new AttendanceId('01JCQR8X9K4ZMTG1WPBV3NH234'),
    employeeId: 'emp-12345',
    tenantId: 'tenant-acme',
    checkInTime: new \DateTimeImmutable('2024-11-15 08:00:00'),
    checkOutTime: null, // Still working
    checkInCoordinates: ['lat' => 3.1390, 'lng' => 101.6869],
    checkOutCoordinates: null,
    notes: 'Early arrival for project deadline'
);
```

### 2. **Work Schedules**

A **Work Schedule** defines expected work hours:
- **Start time** and **end time** (e.g., 9:00 AM - 6:00 PM)
- **Days of week** (e.g., Monday-Friday)
- **Grace periods** (late arrival tolerance, e.g., 15 minutes)
- **Temporal validity** (effective dates, e.g., 2024-01-01 to 2024-12-31)

**Example Domain Object:**
```php
use Nexus\Attendance\Domain\Entities\WorkSchedule;
use Nexus\Attendance\Domain\ValueObjects\ScheduleId;

$schedule = new WorkSchedule(
    id: new ScheduleId('sch-std-9to6'),
    tenantId: 'tenant-acme',
    name: 'Standard 9-to-6',
    startTime: new \DateTimeImmutable('09:00:00'),
    endTime: new \DateTimeImmutable('18:00:00'),
    daysOfWeek: [1, 2, 3, 4, 5], // Monday=1, Friday=5
    graceMinutes: 15, // 15-minute late arrival tolerance
    effectiveFrom: new \DateTimeImmutable('2024-01-01'),
    effectiveTo: new \DateTimeImmutable('2024-12-31')
);
```

### 3. **CQRS Repository Pattern**

The package separates **writes** (commands) from **reads** (queries):

- **Query Interface** (`AttendanceQueryInterface`): Read operations only
  ```php
  $attendance = $attendanceQuery->findById($attendanceId);
  ```

- **Persist Interface** (`AttendancePersistInterface`): Write operations only
  ```php
  $attendanceId = $attendancePersist->save($attendanceRecord);
  ```

**Why CQRS?**
- Clear separation of concerns (read vs write)
- Easier to optimize queries independently
- Better testability (mock only what you need)

### 4. **Immutability Pattern**

All entities and value objects are **immutable** (use `readonly` modifier). To modify, use "with*" methods:

```php
$record = $attendanceQuery->findById($id);

// ❌ WRONG: Cannot modify readonly properties
$record->checkOutTime = new \DateTimeImmutable();

// ✅ CORRECT: Use "with*" method to create new instance
$updatedRecord = $record->withCheckOut(
    new \DateTimeImmutable('2024-11-15 17:30:00'),
    ['lat' => 3.1390, 'lng' => 101.6869]
);

// Save updated record
$attendancePersist->save($updatedRecord);
```

**Benefits:**
- Thread-safe (safe for concurrent operations)
- Predictable state (no hidden mutations)
- Easier to reason about code

### 5. **Domain Services**

The package provides three main services:

- **`AttendanceManager`**: High-level attendance operations (check-in, check-out, retrieve records)
- **`WorkScheduleResolver`**: Determine applicable work schedule for an employee at a specific date/time
- **`OvertimeCalculator`**: Calculate overtime hours based on actual vs scheduled work

---

## Basic Configuration

### Configure Your Framework Container

The package requires you to bind interfaces to concrete implementations.

#### Laravel Example (`app/Providers/AppServiceProvider.php`)

```php
use Nexus\Attendance\Contracts\AttendanceQueryInterface;
use Nexus\Attendance\Contracts\AttendancePersistInterface;
use Nexus\Attendance\Contracts\WorkScheduleQueryInterface;
use Nexus\Attendance\Contracts\WorkSchedulePersistInterface;
use App\Repositories\Attendance\EloquentAttendanceQueryRepository;
use App\Repositories\Attendance\EloquentAttendancePersistRepository;
use App\Repositories\Attendance\EloquentWorkScheduleQueryRepository;
use App\Repositories\Attendance\EloquentWorkSchedulePersistRepository;

public function register(): void
{
    // Bind CQRS repository interfaces
    $this->app->singleton(AttendanceQueryInterface::class, EloquentAttendanceQueryRepository::class);
    $this->app->singleton(AttendancePersistInterface::class, EloquentAttendancePersistRepository::class);
    $this->app->singleton(WorkScheduleQueryInterface::class, EloquentWorkScheduleQueryRepository::class);
    $this->app->singleton(WorkSchedulePersistInterface::class, EloquentWorkSchedulePersistRepository::class);
}
```

#### Symfony Example (`config/services.yaml`)

```yaml
services:
  Nexus\Attendance\Contracts\AttendanceQueryInterface:
    class: App\Repository\Attendance\DoctrineAttendanceQueryRepository
    arguments:
      - '@doctrine.orm.entity_manager'

  Nexus\Attendance\Contracts\AttendancePersistInterface:
    class: App\Repository\Attendance\DoctrineAttendancePersistRepository
    arguments:
      - '@doctrine.orm.entity_manager'

  Nexus\Attendance\Contracts\WorkScheduleQueryInterface:
    class: App\Repository\Attendance\DoctrineWorkScheduleQueryRepository
    arguments:
      - '@doctrine.orm.entity_manager'

  Nexus\Attendance\Contracts\WorkSchedulePersistInterface:
    class: App\Repository\Attendance\DoctrineWorkSchedulePersistRepository
    arguments:
      - '@doctrine.orm.entity_manager'
```

### Create Database Schema

#### Laravel Migration Example

```bash
php artisan make:migration create_attendance_tables
```

```php
// database/migrations/xxxx_xx_xx_create_attendance_tables.php

public function up(): void
{
    Schema::create('attendance_records', function (Blueprint $table) {
        $table->ulid('id')->primary();
        $table->string('employee_id');
        $table->string('tenant_id')->index();
        $table->dateTime('check_in_time');
        $table->dateTime('check_out_time')->nullable();
        $table->json('check_in_coordinates')->nullable();
        $table->json('check_out_coordinates')->nullable();
        $table->string('status', 50);
        $table->text('notes')->nullable();
        $table->timestamps();
        
        $table->index(['employee_id', 'check_in_time']);
        $table->index(['tenant_id', 'check_in_time']);
    });

    Schema::create('work_schedules', function (Blueprint $table) {
        $table->ulid('id')->primary();
        $table->string('tenant_id')->index();
        $table->string('name');
        $table->time('start_time');
        $table->time('end_time');
        $table->json('days_of_week'); // [1,2,3,4,5]
        $table->unsignedInteger('grace_minutes')->default(0);
        $table->date('effective_from');
        $table->date('effective_to')->nullable();
        $table->timestamps();
        
        $table->index(['tenant_id', 'effective_from', 'effective_to']);
    });
}
```

Run migration:
```bash
php artisan migrate
```

---

## Your First Integration

Let's build a simple attendance tracking workflow.

### Step 1: Create Work Schedule

```php
use Nexus\Attendance\Services\AttendanceManager;
use Nexus\Attendance\Domain\Entities\WorkSchedule;
use Nexus\Attendance\Domain\ValueObjects\ScheduleId;

// Inject manager via constructor (dependency injection)
public function __construct(
    private readonly AttendanceManager $attendanceManager
) {}

// Create standard 9-to-6 schedule
$schedule = new WorkSchedule(
    id: ScheduleId::generate(), // Auto-generate ULID
    tenantId: 'tenant-acme',
    name: 'Standard Office Hours',
    startTime: new \DateTimeImmutable('09:00:00'),
    endTime: new \DateTimeImmutable('18:00:00'),
    daysOfWeek: [1, 2, 3, 4, 5], // Mon-Fri
    graceMinutes: 15,
    effectiveFrom: new \DateTimeImmutable('2024-01-01'),
    effectiveTo: null // No end date
);

// Save schedule (uses WorkSchedulePersistInterface internally)
$this->attendanceManager->createSchedule($schedule);
```

### Step 2: Employee Check-In

```php
use Nexus\Attendance\Exceptions\AlreadyCheckedInException;

try {
    $attendanceId = $this->attendanceManager->checkIn(
        employeeId: 'emp-12345',
        tenantId: 'tenant-acme',
        checkInTime: new \DateTimeImmutable('2024-11-15 08:45:00'),
        coordinates: ['lat' => 3.1390, 'lng' => 101.6869],
        notes: 'Early arrival'
    );
    
    echo "Check-in successful! Attendance ID: {$attendanceId}";
    
} catch (AlreadyCheckedInException $e) {
    echo "Error: Employee already checked in today.";
}
```

### Step 3: Employee Check-Out

```php
use Nexus\Attendance\Exceptions\AttendanceNotFoundException;
use Nexus\Attendance\Exceptions\AlreadyCheckedOutException;

try {
    $this->attendanceManager->checkOut(
        attendanceId: $attendanceId,
        checkOutTime: new \DateTimeImmutable('2024-11-15 17:30:00'),
        coordinates: ['lat' => 3.1390, 'lng' => 101.6869]
    );
    
    echo "Check-out successful!";
    
} catch (AttendanceNotFoundException $e) {
    echo "Error: Attendance record not found.";
} catch (AlreadyCheckedOutException $e) {
    echo "Error: Already checked out.";
}
```

### Step 4: Retrieve Attendance Record

```php
$record = $this->attendanceManager->getAttendance($attendanceId);

echo "Employee: {$record->getEmployeeId()}\n";
echo "Check-in: {$record->getCheckInTime()->format('Y-m-d H:i:s')}\n";
echo "Check-out: {$record->getCheckOutTime()?->format('Y-m-d H:i:s') ?? 'Still working'}\n";
echo "Work hours: {$record->getWorkHours()?->getHours()} hours\n";
echo "Status: {$record->getStatus()->value}\n";
```

---

## Common Patterns

### Pattern 1: Daily Attendance Report

```php
use Nexus\Attendance\Contracts\AttendanceQueryInterface;

public function __construct(
    private readonly AttendanceQueryInterface $attendanceQuery
) {}

public function getDailyReport(string $employeeId, \DateTimeImmutable $date): array
{
    $records = $this->attendanceQuery->findByEmployeeAndDateRange(
        employeeId: $employeeId,
        startDate: $date->setTime(0, 0, 0),
        endDate: $date->setTime(23, 59, 59)
    );
    
    $totalHours = array_reduce($records, function ($sum, $record) {
        return $sum + ($record->getWorkHours()?->getHours() ?? 0);
    }, 0);
    
    return [
        'date' => $date->format('Y-m-d'),
        'records' => $records,
        'total_hours' => $totalHours,
    ];
}
```

### Pattern 2: Calculate Overtime

```php
use Nexus\Attendance\Services\OvertimeCalculator;
use Nexus\Attendance\Services\WorkScheduleResolver;

public function __construct(
    private readonly OvertimeCalculator $overtimeCalc,
    private readonly WorkScheduleResolver $scheduleResolver
) {}

public function calculateOvertime(string $employeeId, \DateTimeImmutable $date): float
{
    $record = $this->attendanceQuery->findByEmployeeAndDate($employeeId, $date);
    
    if (!$record || !$record->getWorkHours()) {
        return 0.0;
    }
    
    // Get applicable schedule
    $schedule = $this->scheduleResolver->resolveSchedule($employeeId, $date);
    
    if (!$schedule) {
        return 0.0; // No schedule = no overtime calculation
    }
    
    // Calculate overtime hours
    return $this->overtimeCalc->calculate(
        $record->getWorkHours(),
        $schedule
    );
}
```

### Pattern 3: Bulk Import Historical Data

```php
public function importHistoricalData(array $csvData): void
{
    foreach ($csvData as $row) {
        $record = new AttendanceRecord(
            id: AttendanceId::generate(),
            employeeId: $row['employee_id'],
            tenantId: 'tenant-acme',
            checkInTime: new \DateTimeImmutable($row['check_in']),
            checkOutTime: new \DateTimeImmutable($row['check_out']),
            checkInCoordinates: null,
            checkOutCoordinates: null,
            notes: 'Imported from legacy system'
        );
        
        $this->attendancePersist->save($record);
    }
}
```

---

## Next Steps

Now that you understand the basics, explore:

1. **[API Reference](api-reference.md)** - Complete documentation of all interfaces, entities, and services
2. **[Integration Guide](integration-guide.md)** - Deep dive into Laravel and Symfony integration
3. **[Basic Usage Examples](examples/basic-usage.php)** - Runnable code samples
4. **[Advanced Usage Examples](examples/advanced-usage.php)** - Complex scenarios (multi-day aggregation, late arrival detection)
5. **[Package Requirements](../REQUIREMENTS.md)** - All 45 requirements with tracking codes
6. **[Test Suite Documentation](../TEST_SUITE_SUMMARY.md)** - 46 tests, 100% coverage

---

## Troubleshooting

### Issue: "Interface not found" error

**Error:**
```
Class 'Nexus\Attendance\Contracts\AttendanceQueryInterface' not found
```

**Solution:**
1. Verify package is installed: `composer show nexus/attendance-management`
2. Run `composer dump-autoload`
3. Check PSR-4 autoloading is configured correctly

### Issue: "Cannot modify readonly property"

**Error:**
```
Cannot modify readonly property AttendanceRecord::$checkOutTime
```

**Solution:**
Use "with*" methods instead of direct property assignment:

```php
// ❌ WRONG
$record->checkOutTime = new \DateTimeImmutable();

// ✅ CORRECT
$updated = $record->withCheckOut(new \DateTimeImmutable(), null);
$this->attendancePersist->save($updated);
```

### Issue: "AlreadyCheckedInException" when employee hasn't checked in

**Cause:**
Previous record was not checked out (still in "checked_in" status).

**Solution:**
```php
// Check if employee has open record
$openRecord = $this->attendanceQuery->findOpenRecordByEmployee($employeeId);

if ($openRecord) {
    // Force check-out previous record
    $this->attendanceManager->checkOut(
        $openRecord->getId(),
        new \DateTimeImmutable('yesterday 18:00:00'),
        null
    );
}

// Now check in
$this->attendanceManager->checkIn(...);
```

### Issue: Schedule not applied to overtime calculation

**Cause:**
Schedule's `effectiveFrom` or `effectiveTo` dates exclude the attendance date.

**Solution:**
Verify schedule validity period:

```php
$schedule = $this->scheduleResolver->resolveSchedule($employeeId, $date);

if (!$schedule) {
    // No valid schedule for this date
    echo "No schedule found for date: " . $date->format('Y-m-d');
}
```

Adjust schedule dates if needed:
```php
$schedule = $schedule->withEffectiveFrom(new \DateTimeImmutable('2024-01-01'));
$schedule = $schedule->withEffectiveTo(null); // No end date
$this->workSchedulePersist->save($schedule);
```

### Issue: GPS coordinates not saved

**Cause:**
Coordinates must be an array with 'lat' and 'lng' keys.

**Solution:**
```php
// ❌ WRONG
$coords = [3.1390, 101.6869];

// ✅ CORRECT
$coords = ['lat' => 3.1390, 'lng' => 101.6869];
```

### Issue: "Call to undefined method" when using domain entities

**Cause:**
Domain entities expose only essential methods. Use repositories for queries.

**Solution:**
```php
// ❌ WRONG: Entity doesn't have query methods
$records = $attendanceRecord->findByEmployee($employeeId);

// ✅ CORRECT: Use repository
$records = $this->attendanceQuery->findByEmployee($employeeId);
```

---

## Getting Help

- **GitHub Issues:** https://github.com/nexus/attendance-management/issues
- **Documentation:** `/docs` folder in package root
- **Stack Overflow:** Tag questions with `nexus-attendance`
- **Community Forum:** https://community.nexus-framework.org

---

**Ready to build? Check out the [Integration Guide](integration-guide.md) for framework-specific examples!**
