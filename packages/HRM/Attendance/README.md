# Nexus\AttendanceManagement

**Version:** 1.0.0  
**PHP:** ^8.3  
**License:** MIT  
**Status:** Production Ready ✅

A framework-agnostic PHP package for comprehensive employee attendance tracking with work schedule management, overtime calculation, and flexible check-in/check-out workflows.

---

## 📋 Table of Contents

- [Overview](#overview)
- [Features](#features)
- [Installation](#installation)
- [Architecture](#architecture)
- [Quick Start](#quick-start)
- [Usage Examples](#usage-examples)
- [API Reference](#api-reference)
- [Integration Guide](#integration-guide)
- [Testing](#testing)
- [Contributing](#contributing)
- [License](#license)

---

## 🎯 Overview

The **AttendanceManagement** package provides a complete solution for tracking employee attendance in any PHP application. Built following Clean Architecture and Domain-Driven Design (DDD) principles, it offers:

- **Framework-agnostic** - Works with Laravel, Symfony, or any PHP framework
- **Type-safe** - Leverages PHP 8.3+ features (readonly, enums, strict types)
- **CQRS compliant** - Separate query and command interfaces
- **Testable** - 100% test coverage with PHPUnit
- **Immutable** - Value objects and entities designed for data integrity

**Core Capabilities:**
- Daily check-in/check-out tracking with timestamps
- GPS location capture (optional)
- Work schedule management with day-of-week and effectivity periods
- Automatic overtime calculation
- Late arrival and early departure detection
- Grace period support for flexible scheduling
- Multi-tenant ready

---

## ✨ Features

### ✅ Attendance Tracking
- **Check-in/Check-out**: Record employee arrival and departure with precise timestamps
- **Location Tracking**: Optional GPS coordinates and location notes
- **Status Management**: Draft, checked-in, checked-out, absent states
- **Validation**: Prevent duplicate check-ins, enforce chronological order
- **Work Hours Calculation**: Automatic regular and overtime hours separation

### ✅ Work Schedule Management
- **Flexible Schedules**: Define expected work hours per employee
- **Day-of-Week Filtering**: Schedules active only on specific days (Mon-Fri, etc.)
- **Effectivity Periods**: Temporal validity with `effectiveFrom` and `effectiveTo` dates
- **Grace Periods**: Configurable late arrival tolerance (e.g., 15 minutes)
- **Multi-Schedule Support**: Different schedules for different periods

### ✅ Overtime Calculation
- **Automatic Detection**: Overtime hours calculated from work hours vs schedule
- **Batch Processing**: Calculate total overtime across multiple records
- **Schedule Integration**: Context-aware calculation using employee schedule
- **Threshold Checks**: Validate if overtime was worked

### ✅ Type Safety & Validation
- **Value Objects**: Type-safe identifiers (AttendanceId, ScheduleId)
- **Immutability**: All value objects and entities are immutable
- **Domain Validation**: Business rules enforced at domain level
- **Static Factories**: Descriptive exception creation

---

## 📦 Installation

### Composer Install

```bash
composer require nexus/attendance-management
```

### Framework Integration

The package requires concrete implementations of repository interfaces. See [Integration Guide](#integration-guide) for framework-specific setup.

### Dependencies

```json
{
  "require": {
    "php": "^8.3",
    "psr/log": "^3.0"
  },
  "require-dev": {
    "phpunit/phpunit": "^10.0"
  }
}
```

---

## 🏗️ Architecture

### Clean Architecture Layers

```
┌─────────────────────────────────────────────┐
│  Application Layer (Your Framework)        │
│  - Controllers                              │
│  - Repository Implementations (Eloquent)   │
│  - Service Bindings                        │
└─────────────────┬───────────────────────────┘
                  │ depends on
                  ▼
┌─────────────────────────────────────────────┐
│  Domain Layer (AttendanceManagement)       │
│  - Entities (AttendanceRecord, Schedule)   │
│  - Value Objects (AttendanceId, WorkHours) │
│  - Services (AttendanceManager)            │
│  - Interfaces (Repository Contracts)       │
└─────────────────────────────────────────────┘
```

### CQRS Pattern

Repository interfaces follow **Command Query Responsibility Segregation**:

**Query Interfaces (Read Operations):**
- `AttendanceQueryInterface` - Find and search attendance records
- `WorkScheduleQueryInterface` - Retrieve effective schedules

**Persist Interfaces (Write Operations):**
- `AttendancePersistInterface` - Save and delete attendance records
- `WorkSchedulePersistInterface` - Manage schedules

**Benefits:**
- **Single Responsibility** - Each interface has one clear purpose
- **Interface Segregation** - Consumers inject only what they need
- **Testability** - Mock read or write operations independently

### Domain-Driven Design

**Value Objects:**
- `AttendanceId` - Type-safe attendance identifier
- `ScheduleId` - Type-safe schedule identifier  
- `WorkHours` - Immutable work hours with regular/overtime split

**Entities:**
- `AttendanceRecord` - Core attendance event with check-in/out lifecycle
- `WorkSchedule` - Expected work schedule with temporal validity

**Domain Services:**
- `AttendanceManager` - Orchestrates check-in/check-out workflows
- `WorkScheduleResolver` - Resolves applicable schedule for employee/date
- `OvertimeCalculator` - Computes overtime hours from records

**Enums:**
- `AttendanceStatus` - DRAFT, CHECKED_IN, CHECKED_OUT, ABSENT
- `CheckType` - NORMAL, REMOTE, ON_SITE

---

## 🚀 Quick Start

### 1. Define Repository Implementations

```php
use Tests\TestCase;
use App\Models\User;
use App\Repositories\*;

// Laravel Eloquent Example
final readonly class EloquentAttendanceQuery implements AttendanceQueryInterface
{
    public function __construct(private AttendanceModel $model) {}
    
    public function findById(string $id): ?AttendanceRecordInterface
    {
        return $this->model->find($id);
    }
    
    public function findByEmployeeAndDate(
        string $employeeId,
        \DateTimeImmutable $date
    ): ?AttendanceRecordInterface {
        return $this->model
            ->where('employee_id', $employeeId)
            ->whereDate('date', $date->format('Y-m-d'))
            ->first();
    }
    
    // ... implement remaining methods
}
```

### 2. Bind Interfaces to Implementations

```php
// Laravel Service Provider
use App\Models\Attendance;
use App\Models\Attendance;

public function register(): void
{
    $this->app->singleton(AttendanceQueryInterface::class, EloquentAttendanceQuery::class);
    $this->app->singleton(AttendancePersistInterface::class, EloquentAttendancePersist::class);
    $this->app->singleton(WorkScheduleQueryInterface::class, EloquentScheduleQuery::class);
    
    $this->app->singleton(AttendanceManagerInterface::class, AttendanceManager::class);
}
```

### 3. Use AttendanceManager in Your Application

```php
use PHPUnit\Framework\TestCase;

final readonly class AttendanceController
{
    public function __construct(
        private AttendanceManagerInterface $attendanceManager
    ) {}
    
    public function checkIn(Request $request): JsonResponse
    {
        $record = $this->attendanceManager->checkIn(
            employeeId: $request->user()->employee_id,
            checkInTime: new \DateTimeImmutable(),
            location: $request->input('location'),
            latitude: $request->input('latitude'),
            longitude: $request->input('longitude')
        );
        
        return response()->json([
            'id' => $record->getId()->toString(),
            'status' => $record->getStatus()->value,
            'check_in_time' => $record->getCheckInTime()?->format('Y-m-d H:i:s'),
        ]);
    }
}
```

---

## 📖 Usage Examples

### Example 1: Employee Check-In

```php
use Nexus\Attendance\Services\*;

class AttendanceService
{
    public function __construct(
        private AttendanceManagerInterface $manager
    ) {}
    
    public function recordCheckIn(string $employeeId): void
    {
        $record = $this->manager->checkIn(
            employeeId: $employeeId,
            checkInTime: new \DateTimeImmutable('2024-01-15 08:00:00'),
            location: 'Main Office',
            latitude: 3.1390,
            longitude: 101.6869
        );
        
        echo "Checked in at: " . $record->getCheckInTime()->format('H:i:s');
        // Output: Checked in at: 08:00:00
    }
}
```

### Example 2: Employee Check-Out with Overtime

```php
public function recordCheckOut(string $employeeId): void
{
    $record = $this->manager->checkOut(
        employeeId: $employeeId,
        date: new \DateTimeImmutable('2024-01-15'),
        checkOutTime: new \DateTimeImmutable('2024-01-15 19:30:00'),
        location: 'Main Office'
    );
    
    if ($record->isComplete()) {
        $hours = $record->getWorkHours();
        
        echo sprintf(
            "Total: %.2f hours (Regular: %.2f, Overtime: %.2f)\n",
            $hours->getTotalHours(),
            $hours->getRegularHours(),
            $hours->getOvertimeHours()
        );
        // Output: Total: 11.50 hours (Regular: 8.00, Overtime: 3.50)
    }
}
```

### Example 3: Work Schedule Resolution

```php
use Nexus\Attendance\Contracts\*;

class ScheduleService
{
    public function __construct(
        private WorkScheduleResolverInterface $resolver
    ) {}
    
    public function getEmployeeSchedule(string $employeeId): void
    {
        $today = new \DateTimeImmutable();
        
        // Safe resolution (returns null if not found)
        $schedule = $this->resolver->tryResolveSchedule($employeeId, $today);
        
        if ($schedule) {
            echo sprintf(
                "Expected: %s - %s (%.2f hours)\n",
                $schedule->getStartTime()->format('H:i'),
                $schedule->getEndTime()->format('H:i'),
                $schedule->getStandardHours()
            );
            // Output: Expected: 08:00 - 17:00 (8.00 hours)
        } else {
            echo "No schedule defined for today\n";
        }
    }
}
```

### Example 4: Late Arrival Detection

```php
public function checkLateArrival(string $employeeId): void
{
    $checkInTime = new \DateTimeImmutable('2024-01-15 08:20:00');
    
    $schedule = $this->resolver->resolveSchedule(
        $employeeId,
        $checkInTime
    );
    
    if ($schedule->isLateCheckIn($checkInTime)) {
        $lateMinutes = ($checkInTime->getTimestamp() - 
                       $schedule->getStartTime()->getTimestamp()) / 60;
        
        echo "Employee is late by {$lateMinutes} minutes\n";
        // Output: Employee is late by 20 minutes
        
        // But with grace period (15 minutes), might still be acceptable
        if ($lateMinutes <= $schedule->getGraceMinutes()) {
            echo "Within grace period - no penalty\n";
        }
    }
}
```

### Example 5: Overtime Calculation for Payroll

```php
use Nexus\Attendance\Enums\CheckType;
use Nexus\Attendance\Enums\CheckType;

class PayrollService
{
    public function __construct(
        private OvertimeCalculatorInterface $calculator,
        private AttendanceQueryInterface $query
    ) {}
    
    public function calculateMonthlyOvertime(
        string $employeeId,
        int $year,
        int $month
    ): float {
        $startDate = new \DateTimeImmutable("{$year}-{$month}-01");
        $endDate = $startDate->modify('last day of this month');
        
        $records = $this->query->findByEmployeeAndDateRange(
            $employeeId,
            $startDate,
            $endDate
        );
        
        $totalOvertime = $this->calculator->calculateTotalOvertime($records);
        
        echo "Total overtime for {$startDate->format('F Y')}: {$totalOvertime} hours\n";
        // Output: Total overtime for January 2024: 12.50 hours
        
        return $totalOvertime;
    }
}
```

### Example 6: Absence Detection

```php
public function checkAbsences(string $employeeId, \DateTimeImmutable $date): void
{
    $hasCheckedIn = $this->query->hasCheckedInToday($employeeId, $date);
    
    if (!$hasCheckedIn && $this->resolver->hasSchedule($employeeId, $date)) {
        echo "Employee {$employeeId} is absent on {$date->format('Y-m-d')}\n";
        
        // Mark as absent (implementation specific)
        // $this->markAsAbsent($employeeId, $date);
    }
}
```

### Example 7: Remote Work Check-In

```php
use Illuminate\Support\Facades\Schema;

public function checkInRemotely(string $employeeId): void
{
    $record = $this->manager->checkIn(
        employeeId: $employeeId,
        checkInTime: new \DateTimeImmutable(),
        location: 'Home Office',
        latitude: null, // No GPS for privacy
        longitude: null,
        checkType: CheckType::REMOTE
    );
    
    echo "Remote check-in recorded: " . $record->getCheckType()->value;
    // Output: Remote check-in recorded: REMOTE
}
```

---

## 📚 API Reference

### Value Objects

#### AttendanceId

Type-safe unique identifier for attendance records.

```php
namespace Nexus\Attendance\ValueObjects;

final readonly class AttendanceId
{
    public function __construct(public string $value);
    
    public function equals(AttendanceId $other): bool;
    public function toString(): string;
}
```

**Usage:**
```php
$id = new AttendanceId('ATT-2024-001');
echo $id->toString(); // "ATT-2024-001"
```

#### WorkHours

Immutable work hours with automatic overtime calculation.

```php
final readonly class WorkHours
{
    public function __construct(
        public float $regularHours,
        public float $overtimeHours
    );
    
    public static function fromDuration(
        \DateTimeImmutable $checkIn,
        \DateTimeImmutable $checkOut,
        float $standardHours = 8.0
    ): self;
    
    public function getTotalHours(): float;
}
```

**Usage:**
```php
$hours = WorkHours::fromDuration(
    checkIn: new \DateTimeImmutable('08:00:00'),
    checkOut: new \DateTimeImmutable('19:30:00'),
    standardHours: 8.0
);

echo $hours->getTotalHours();    // 11.5
echo $hours->getRegularHours();  // 8.0
echo $hours->getOvertimeHours(); // 3.5
```

---

### Entities

#### AttendanceRecord

Core domain entity representing a daily attendance event.

```php
namespace Nexus\Attendance\Entities;

final readonly class AttendanceRecord implements AttendanceRecordInterface
{
    public function getId(): AttendanceId;
    public function getEmployeeId(): string;
    public function getDate(): \DateTimeImmutable;
    public function getStatus(): AttendanceStatus;
    
    public function getCheckInTime(): ?\DateTimeImmutable;
    public function getCheckOutTime(): ?\DateTimeImmutable;
    public function getCheckInLocation(): ?string;
    public function getCheckOutLocation(): ?string;
    public function getCheckInLatitude(): ?float;
    public function getCheckInLongitude(): ?float;
    public function getCheckOutLatitude(): ?float;
    public function getCheckOutLongitude(): ?float;
    
    public function getCheckType(): CheckType;
    public function getWorkHours(): ?WorkHours;
    
    public function isCheckedIn(): bool;
    public function isComplete(): bool;
    
    // Immutable update methods
    public function withCheckIn(
        \DateTimeImmutable $time,
        ?string $location = null,
        ?float $latitude = null,
        ?float $longitude = null
    ): self;
    
    public function withCheckOut(
        \DateTimeImmutable $time,
        ?string $location = null,
        ?float $latitude = null,
        ?float $longitude = null
    ): self;
}
```

**Usage:**
```php
$record = new AttendanceRecord(
    id: new AttendanceId('ATT-001'),
    employeeId: 'EMP-123',
    date: new \DateTimeImmutable('2024-01-15'),
    status: AttendanceStatus::DRAFT
);

// Check-in (creates new instance)
$checkedIn = $record->withCheckIn(
    time: new \DateTimeImmutable('2024-01-15 08:00:00'),
    location: 'Main Office'
);

// Check-out (creates new instance)
$complete = $checkedIn->withCheckOut(
    time: new \DateTimeImmutable('2024-01-15 17:00:00')
);

echo $complete->getWorkHours()->getTotalHours(); // 9.0
```

#### WorkSchedule

Represents expected work schedule with temporal validity.

```php
final readonly class WorkSchedule implements WorkScheduleInterface
{
    public function getId(): ScheduleId;
    public function getEmployeeId(): string;
    public function getName(): string;
    
    public function getStartTime(): \DateTimeImmutable;
    public function getEndTime(): \DateTimeImmutable;
    public function getStandardHours(): float;
    
    public function getDaysOfWeek(): array; // [1,2,3,4,5] for Mon-Fri
    public function getGraceMinutes(): int;
    
    public function getEffectiveFrom(): \DateTimeImmutable;
    public function getEffectiveTo(): ?\DateTimeImmutable;
    
    public function isEffectiveOn(\DateTimeImmutable $date): bool;
    public function isLateCheckIn(\DateTimeImmutable $time): bool;
    public function isEarlyCheckOut(\DateTimeImmutable $time): bool;
}
```

**Usage:**
```php
$schedule = new WorkSchedule(
    id: new ScheduleId('SCH-001'),
    employeeId: 'EMP-123',
    name: 'Standard Office Hours',
    startTime: new \DateTimeImmutable('08:00:00'),
    endTime: new \DateTimeImmutable('17:00:00'),
    standardHours: 8.0,
    daysOfWeek: [1, 2, 3, 4, 5], // Monday to Friday
    graceMinutes: 15,
    effectiveFrom: new \DateTimeImmutable('2024-01-01'),
    effectiveTo: null
);

// Check if schedule applies on date
$monday = new \DateTimeImmutable('2024-01-15'); // Monday
echo $schedule->isEffectiveOn($monday); // true

$saturday = new \DateTimeImmutable('2024-01-20'); // Saturday
echo $schedule->isEffectiveOn($saturday); // false

// Late check detection
$lateTime = new \DateTimeImmutable('2024-01-15 08:10:00');
echo $schedule->isLateCheckIn($lateTime); // false (within grace)

$veryLateTime = new \DateTimeImmutable('2024-01-15 08:30:00');
echo $schedule->isLateCheckIn($veryLateTime); // true (beyond grace)
```

---

### Domain Services

#### AttendanceManager

Main orchestration service for attendance workflows.

```php
namespace Nexus\Attendance\Services;

final readonly class AttendanceManager implements AttendanceManagerInterface
{
    public function __construct(
        private AttendanceQueryInterface $query,
        private AttendancePersistInterface $persist,
        private WorkScheduleQueryInterface $scheduleQuery,
        private LoggerInterface $logger = new NullLogger()
    );
    
    public function checkIn(
        string $employeeId,
        \DateTimeImmutable $checkInTime,
        ?string $location = null,
        ?float $latitude = null,
        ?float $longitude = null,
        CheckType $checkType = CheckType::NORMAL
    ): AttendanceRecordInterface;
    
    public function checkOut(
        string $employeeId,
        \DateTimeImmutable $date,
        \DateTimeImmutable $checkOutTime,
        ?string $location = null,
        ?float $latitude = null,
        ?float $longitude = null
    ): AttendanceRecordInterface;
}
```

**Exceptions:**
- `InvalidCheckTimeException::alreadyCheckedIn()` - Employee already checked in
- `InvalidCheckTimeException::notCheckedIn()` - No check-in record found
- `InvalidCheckTimeException::checkOutBeforeCheckIn()` - Check-out before check-in

#### WorkScheduleResolver

Resolves applicable work schedule for employee on specific date.

```php
final readonly class WorkScheduleResolver implements WorkScheduleResolverInterface
{
    public function __construct(
        private WorkScheduleQueryInterface $query
    );
    
    // Throws exception if not found
    public function resolveSchedule(
        string $employeeId,
        \DateTimeImmutable $date
    ): WorkScheduleInterface;
    
    // Returns null if not found
    public function tryResolveSchedule(
        string $employeeId,
        \DateTimeImmutable $date
    ): ?WorkScheduleInterface;
    
    // Boolean check
    public function hasSchedule(
        string $employeeId,
        \DateTimeImmutable $date
    ): bool;
}
```

**Exceptions:**
- `WorkScheduleNotFoundException::forEmployee()` - No schedule found

#### OvertimeCalculator

Calculates overtime hours from attendance records.

```php
final readonly class OvertimeCalculator implements OvertimeCalculatorInterface
{
    // Calculate from record's work hours
    public function calculateOvertime(
        AttendanceRecordInterface $record
    ): float;
    
    // Calculate with schedule context
    public function calculateOvertimeWithSchedule(
        AttendanceRecordInterface $record,
        WorkScheduleInterface $schedule
    ): float;
    
    // Aggregate total from multiple records
    public function calculateTotalOvertime(array $records): float;
    
    // Check if overtime was worked
    public function isOvertimeExceeded(
        AttendanceRecordInterface $record
    ): bool;
}
```

---

### Repository Interfaces (CQRS)

#### AttendanceQueryInterface (Read Operations)

```php
namespace Nexus\Attendance\Contracts;

interface AttendanceQueryInterface
{
    public function findById(string $id): ?AttendanceRecordInterface;
    
    public function findByEmployeeAndDate(
        string $employeeId,
        \DateTimeImmutable $date
    ): ?AttendanceRecordInterface;
    
    public function findByEmployeeAndDateRange(
        string $employeeId,
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate
    ): array;
    
    public function hasCheckedInToday(
        string $employeeId,
        \DateTimeImmutable $date
    ): bool;
}
```

#### AttendancePersistInterface (Write Operations)

```php
interface AttendancePersistInterface
{
    public function save(
        AttendanceRecordInterface $record
    ): AttendanceRecordInterface;
    
    public function delete(string $id): void;
}
```

#### WorkScheduleQueryInterface (Read Operations)

```php
interface WorkScheduleQueryInterface
{
    public function findById(string $id): ?WorkScheduleInterface;
    
    public function findByEmployeeId(string $employeeId): array;
    
    public function findEffectiveSchedule(
        string $employeeId,
        \DateTimeImmutable $date
    ): ?WorkScheduleInterface;
}
```

#### WorkSchedulePersistInterface (Write Operations)

```php
interface WorkSchedulePersistInterface
{
    public function save(
        WorkScheduleInterface $schedule
    ): WorkScheduleInterface;
    
    public function delete(string $id): void;
}
```

---

## 🔌 Integration Guide

### Laravel Integration

#### 1. Create Eloquent Models

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Schema\Blueprint;
use Nexus\Attendance\Enums\AttendanceStatus;
use Nexus\Attendance\ValueObjects\WorkHours;
use Illuminate\Database\Migrations\Migration;
use Nexus\Attendance\Entities\AttendanceRecord;

class Attendance extends Model implements AttendanceRecordInterface
{
    protected $fillable = [
        'employee_id', 'date', 'status', 'check_in_time',
        'check_out_time', 'check_in_location', 'check_out_location',
        'check_in_latitude', 'check_in_longitude',
        'check_out_latitude', 'check_out_longitude',
        'check_type', 'regular_hours', 'overtime_hours'
    ];
    
    protected $casts = [
        'date' => 'immutable_datetime',
        'check_in_time' => 'immutable_datetime',
        'check_out_time' => 'immutable_datetime',
        'status' => AttendanceStatus::class,
        'check_type' => CheckType::class,
    ];
    
    public function getId(): AttendanceId
    {
        return new AttendanceId($this->id);
    }
    
    public function getEmployeeId(): string
    {
        return $this->employee_id;
    }
    
    public function getDate(): \DateTimeImmutable
    {
        return $this->date;
    }
    
    public function getWorkHours(): ?WorkHours
    {
        if ($this->regular_hours === null) {
            return null;
        }
        
        return new WorkHours(
            regularHours: $this->regular_hours,
            overtimeHours: $this->overtime_hours ?? 0.0
        );
    }
    
    // ... implement remaining interface methods
}
```

#### 2. Create Repository Implementations

```php
namespace App\Repositories;

use Nexus\Attendance\ValueObjects\AttendanceId;
use Symfony\Component\Routing\Annotation\Route;
use Nexus\Attendance\Services\AttendanceManager;

final readonly class EloquentAttendanceQuery implements AttendanceQueryInterface
{
    public function findById(string $id): ?AttendanceRecordInterface
    {
        return Attendance::find($id);
    }
    
    public function findByEmployeeAndDate(
        string $employeeId,
        \DateTimeImmutable $date
    ): ?AttendanceRecordInterface {
        return Attendance::query()
            ->where('employee_id', $employeeId)
            ->whereDate('date', $date->format('Y-m-d'))
            ->first();
    }
    
    public function findByEmployeeAndDateRange(
        string $employeeId,
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate
    ): array {
        return Attendance::query()
            ->where('employee_id', $employeeId)
            ->whereBetween('date', [
                $startDate->format('Y-m-d'),
                $endDate->format('Y-m-d')
            ])
            ->get()
            ->all();
    }
    
    public function hasCheckedInToday(
        string $employeeId,
        \DateTimeImmutable $date
    ): bool {
        return Attendance::query()
            ->where('employee_id', $employeeId)
            ->whereDate('date', $date->format('Y-m-d'))
            ->whereNotNull('check_in_time')
            ->exists();
    }
}
```

```php
namespace App\Repositories;

use Nexus\Attendance\Services\AttendanceManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\JsonResponse;

final readonly class EloquentAttendancePersist implements AttendancePersistInterface
{
    public function save(AttendanceRecordInterface $record): AttendanceRecordInterface
    {
        $data = [
            'employee_id' => $record->getEmployeeId(),
            'date' => $record->getDate()->format('Y-m-d'),
            'status' => $record->getStatus(),
            'check_in_time' => $record->getCheckInTime()?->format('Y-m-d H:i:s'),
            'check_out_time' => $record->getCheckOutTime()?->format('Y-m-d H:i:s'),
            'check_in_location' => $record->getCheckInLocation(),
            'check_out_location' => $record->getCheckOutLocation(),
            'check_in_latitude' => $record->getCheckInLatitude(),
            'check_in_longitude' => $record->getCheckInLongitude(),
            'check_out_latitude' => $record->getCheckOutLatitude(),
            'check_out_longitude' => $record->getCheckOutLongitude(),
            'check_type' => $record->getCheckType(),
            'regular_hours' => $record->getWorkHours()?->getRegularHours(),
            'overtime_hours' => $record->getWorkHours()?->getOvertimeHours(),
        ];
        
        $attendance = Attendance::updateOrCreate(
            ['id' => $record->getId()->toString()],
            $data
        );
        
        return $attendance;
    }
    
    public function delete(string $id): void
    {
        Attendance::destroy($id);
    }
}
```

#### 3. Register in Service Provider

```php
namespace App\Providers;

use Nexus\Attendance\Contracts\AttendanceQueryInterface;
use Nexus\Attendance\Contracts\AttendanceQueryInterface;
use Nexus\Attendance\Contracts\AttendanceQueryInterface;
use Nexus\Attendance\Contracts\AttendanceQueryInterface;

class AttendanceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind query interfaces
        $this->app->singleton(
            AttendanceQueryInterface::class,
            EloquentAttendanceQuery::class
        );
        
        $this->app->singleton(
            WorkScheduleQueryInterface::class,
            EloquentWorkScheduleQuery::class
        );
        
        // Bind persist interfaces
        $this->app->singleton(
            AttendancePersistInterface::class,
            EloquentAttendancePersist::class
        );
        
        $this->app->singleton(
            WorkSchedulePersistInterface::class,
            EloquentWorkSchedulePersist::class
        );
        
        // Bind domain services
        $this->app->singleton(
            AttendanceManagerInterface::class,
            AttendanceManager::class
        );
        
        $this->app->singleton(
            WorkScheduleResolverInterface::class,
            WorkScheduleResolver::class
        );
        
        $this->app->singleton(
            OvertimeCalculatorInterface::class,
            OvertimeCalculator::class
        );
    }
}
```

#### 4. Create Migration

```php
use Nexus\Attendance\Contracts\AttendanceRecordInterface;
use Nexus\Attendance\Contracts\AttendanceRecordInterface;
use Nexus\Attendance\Contracts\AttendanceRecordInterface;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->string('employee_id')->index();
            $table->date('date')->index();
            $table->string('status');
            
            $table->timestamp('check_in_time')->nullable();
            $table->timestamp('check_out_time')->nullable();
            
            $table->string('check_in_location')->nullable();
            $table->string('check_out_location')->nullable();
            
            $table->decimal('check_in_latitude', 10, 7)->nullable();
            $table->decimal('check_in_longitude', 10, 7)->nullable();
            $table->decimal('check_out_latitude', 10, 7)->nullable();
            $table->decimal('check_out_longitude', 10, 7)->nullable();
            
            $table->string('check_type')->default('NORMAL');
            
            $table->decimal('regular_hours', 5, 2)->nullable();
            $table->decimal('overtime_hours', 5, 2)->nullable();
            
            $table->timestamps();
            
            $table->unique(['employee_id', 'date']);
        });
        
        Schema::create('work_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('employee_id')->index();
            $table->string('name');
            
            $table->time('start_time');
            $table->time('end_time');
            $table->decimal('standard_hours', 5, 2);
            
            $table->json('days_of_week'); // [1,2,3,4,5]
            $table->integer('grace_minutes')->default(0);
            
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            
            $table->timestamps();
        });
    }
    
    public function down(): void
    {
        Schema::dropIfExists('attendances');
        Schema::dropIfExists('work_schedules');
    }
};
```

### Symfony Integration

#### 1. Configure Services (services.yaml)

```yaml
services:
    # Repository implementations
    App\Repository\DoctrineAttendanceQuery:
        autowire: true
        
    App\Repository\DoctrineAttendancePersist:
        autowire: true
        
    App\Repository\DoctrineWorkScheduleQuery:
        autowire: true
        
    # Bind interfaces
    Nexus\Attendance\Contracts\AttendanceQueryInterface:
        alias: App\Repository\DoctrineAttendanceQuery
        
    Nexus\Attendance\Contracts\AttendancePersistInterface:
        alias: App\Repository\DoctrineAttendancePersist
        
    Nexus\Attendance\Contracts\WorkScheduleQueryInterface:
        alias: App\Repository\DoctrineWorkScheduleQuery
        
    # Domain services
    Nexus\Attendance\Services\AttendanceManager:
        autowire: true
        
    Nexus\Attendance\Contracts\AttendanceManagerInterface:
        alias: Nexus\Attendance\Services\AttendanceManager
```

#### 2. Use in Controller

```php
namespace App\Controller;

use Nexus\Attendance\Contracts\AttendanceRecordInterface;
use Nexus\Attendance\Contracts\AttendanceManagerInterface;
use Nexus\Attendance\Contracts\AttendanceManagerInterface;
use Nexus\Attendance\Contracts\AttendanceManagerInterface;

class AttendanceController extends AbstractController
{
    public function __construct(
        private readonly AttendanceManagerInterface $attendanceManager
    ) {}
    
    #[Route('/api/attendance/check-in', methods: ['POST'])]
    public function checkIn(): JsonResponse
    {
        $employeeId = $this->getUser()->getEmployeeId();
        
        $record = $this->attendanceManager->checkIn(
            employeeId: $employeeId,
            checkInTime: new \DateTimeImmutable()
        );
        
        return $this->json([
            'id' => $record->getId()->toString(),
            'status' => $record->getStatus()->value,
        ]);
    }
}
```

---

## 🧪 Testing

### Running Tests

```bash
# Run all tests
./vendor/bin/phpunit

# Run unit tests only
./vendor/bin/phpunit --testsuite=Unit

# Run with testdox output (human-readable)
./vendor/bin/phpunit --testdox

# Run with coverage
./vendor/bin/phpunit --coverage-html coverage/
```

### Test Coverage

**Current Coverage: 100% ✅**

- **46 tests**
- **95 assertions**
- **0 failures**
- **0 errors**

**Test Suite Breakdown:**

```
Value Objects (15 tests):
 ✓ AttendanceIdTest (4 tests)
 ✓ WorkHoursTest (7 tests)
 ✓ ScheduleIdTest (4 tests)

Entities (13 tests):
 ✓ AttendanceRecordTest (5 tests)
 ✓ WorkScheduleTest (8 tests)

Services (18 tests):
 ✓ AttendanceManagerTest (7 tests)
 ✓ WorkScheduleResolverTest (5 tests)
 ✓ OvertimeCalculatorTest (6 tests)
```

### Writing Tests

#### Example: Testing AttendanceManager

```php
namespace Nexus\Attendance\Tests\Unit\Services;

use Nexus\Attendance\Contracts\AttendanceManagerInterface;
use Nexus\Attendance\Contracts\AttendancePersistInterface;
use Nexus\Attendance\Contracts\AttendancePersistInterface;
use Nexus\Attendance\Contracts\AttendancePersistInterface;

final class AttendanceManagerTest extends TestCase
{
    public function test_check_in_creates_attendance_record(): void
    {
        // Arrange
        $query = $this->createMock(AttendanceQueryInterface::class);
        $query->method('hasCheckedInToday')->willReturn(false);
        
        $persist = $this->createMock(AttendancePersistInterface::class);
        $persist->method('save')
            ->willReturnCallback(fn($record) => $record);
        
        $scheduleQuery = $this->createMock(WorkScheduleQueryInterface::class);
        
        $manager = new AttendanceManager($query, $persist, $scheduleQuery);
        
        // Act
        $record = $manager->checkIn(
            employeeId: 'EMP-123',
            checkInTime: new \DateTimeImmutable('2024-01-15 08:00:00')
        );
        
        // Assert
        $this->assertNotNull($record);
        $this->assertTrue($record->isCheckedIn());
        $this->assertEquals('EMP-123', $record->getEmployeeId());
    }
}
```

### Integration Testing

For integration tests with real database, use Laravel's testing features:

```php
namespace Tests\Feature;

use Nexus\Attendance\Contracts\OvertimeCalculatorInterface;
use Nexus\Attendance\Contracts\WorkScheduleResolverInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class AttendanceTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_employee_can_check_in(): void
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)->postJson('/api/attendance/check-in', [
            'location' => 'Main Office',
        ]);
        
        $response->assertSuccessful();
        $this->assertDatabaseHas('attendances', [
            'employee_id' => $user->employee_id,
            'date' => now()->format('Y-m-d'),
        ]);
    }
}
```

---

## 🤝 Contributing

We welcome contributions! Please follow these guidelines:

### Development Setup

```bash
# Clone the repository
git clone https://github.com/nexus/attendance-management.git
cd attendance-management

# Install dependencies
composer install

# Run tests
./vendor/bin/phpunit
```

### Coding Standards

- **PHP 8.3+** features required (readonly, strict_types, enums)
- **PSR-12** coding style
- **100% test coverage** for new features
- **Type hints** on all method parameters and return types
- **Immutability** for value objects and entities
- **CQRS** pattern for repository interfaces
- **Interface Segregation Principle** (ISP) compliance

### Pull Request Process

1. Create feature branch (`git checkout -b feature/my-feature`)
2. Write tests first (TDD approach)
3. Implement feature
4. Ensure all tests pass
5. Update documentation
6. Submit pull request

### Architecture Compliance

All contributions must adhere to:
- **Clean Architecture** - Domain logic independent of frameworks
- **Domain-Driven Design** - Rich domain model with value objects and entities
- **SOLID Principles** - Especially ISP and DIP
- **Framework Agnostic** - No framework-specific code in domain layer

---

## 📄 License

This package is licensed under the MIT License.

```
MIT License

Copyright (c) 2024 Nexus

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
```

---

## 📖 Documentation

### Package Documentation

Comprehensive documentation is available to help you get started and integrate the Attendance package into your application:

- **[Getting Started Guide](docs/getting-started.md)** - Prerequisites, installation, core concepts, and your first integration
- **[API Reference](docs/api-reference.md)** - Complete documentation for all interfaces, entities, value objects, and services
- **[Integration Guide](docs/integration-guide.md)** - Framework-specific integration for Laravel and Symfony with database migrations, ORM models, and API examples
- **[Basic Usage Examples](docs/examples/basic-usage.php)** - Runnable examples demonstrating simple check-in/check-out workflows
- **[Advanced Usage Examples](docs/examples/advanced-usage.php)** - Complex scenarios including overtime calculation, late arrival detection, and compliance reporting

### Additional Resources

For developers contributing to or evaluating this package:

- **[Package Requirements](REQUIREMENTS.md)** - Detailed requirements tracking with 45 documented requirements (ARC, BUS, FUN, PER, SEC, INT)
- **[Test Suite Summary](TEST_SUITE_SUMMARY.md)** - Comprehensive test documentation with 100% coverage (46 tests, 95 assertions)
- **[Valuation Matrix](VALUATION_MATRIX.md)** - Package valuation analysis and business value assessment ($22,000 valuation)
- **[Implementation Summary](IMPLEMENTATION_SUMMARY.md)** - Implementation progress, architectural decisions, and TDD methodology

### Quick Reference

| Documentation | Description |
|--------------|-------------|
| Getting Started | Quickest path to integration (15 min read) |
| API Reference | All public interfaces and methods |
| Integration Guide | Laravel/Symfony step-by-step setup |
| Basic Examples | Copy-paste ready code samples |
| Advanced Examples | Complex use cases and patterns |
| Requirements | Full requirement traceability |
| Test Suite | Quality metrics and test catalog |
| Valuation | ROI and business value analysis |

**Tip:** Start with the [Getting Started Guide](docs/getting-started.md) for a quick 15-minute introduction, then refer to the [API Reference](docs/api-reference.md) and [Integration Guide](docs/integration-guide.md) for deeper implementation details.

---

## 📞 Support

- **Documentation**: [https://nexus-docs.example.com/attendance](https://nexus-docs.example.com/attendance)
- **Issues**: [GitHub Issues](https://github.com/nexus/attendance-management/issues)
- **Email**: support@nexus.example.com

---

## 🙏 Acknowledgments

Built with ❤️ following:
- **Clean Architecture** by Robert C. Martin
- **Domain-Driven Design** by Eric Evans
- **CQRS Pattern** by Greg Young

---

**Version:** 1.0.0  
**Last Updated:** 2024-01-15  
**Status:** Production Ready ✅
