# API Reference: Nexus Attendance

**Package:** `nexus/attendance-management`  
**Namespace:** `Nexus\Attendance`  
**Version:** 1.0.0

This document provides complete API documentation for all public interfaces, entities, value objects, services, enums, and exceptions.

---

## Table of Contents

1. [Contracts (Interfaces)](#contracts-interfaces)
2. [Domain Entities](#domain-entities)
3. [Value Objects](#value-objects)
4. [Domain Services](#domain-services)
5. [Enumerations](#enumerations)
6. [Exceptions](#exceptions)
7. [Usage Examples](#usage-examples)

---

## Contracts (Interfaces)

### AttendanceQueryInterface

**Namespace:** `Nexus\Attendance\Contracts\AttendanceQueryInterface`

Read-only operations for attendance records (CQRS Query Model).

#### Methods

##### `findById(AttendanceId $id): ?AttendanceRecordInterface`

Find attendance record by ID.

**Parameters:**
- `$id` (AttendanceId) - Unique attendance identifier

**Returns:**
- `AttendanceRecordInterface|null` - Attendance record or null if not found

**Throws:**
- `InvalidAttendanceIdException` - If ID is invalid

**Example:**
```php
use Nexus\Attendance\Domain\ValueObjects\AttendanceId;

$record = $attendanceQuery->findById(
    new AttendanceId('01JCQR8X9K4ZMTG1WPBV3NH234')
);

if ($record) {
    echo "Found record for employee: {$record->getEmployeeId()}";
}
```

---

##### `findByEmployee(string $employeeId): array`

Find all attendance records for an employee.

**Parameters:**
- `$employeeId` (string) - Employee identifier

**Returns:**
- `array<AttendanceRecordInterface>` - Array of attendance records

**Example:**
```php
$records = $attendanceQuery->findByEmployee('emp-12345');

foreach ($records as $record) {
    echo "{$record->getCheckInTime()->format('Y-m-d H:i:s')}\n";
}
```

---

##### `findByEmployeeAndDateRange(string $employeeId, \DateTimeImmutable $startDate, \DateTimeImmutable $endDate): array`

Find attendance records for an employee within a date range.

**Parameters:**
- `$employeeId` (string) - Employee identifier
- `$startDate` (DateTimeImmutable) - Start of date range (inclusive)
- `$endDate` (DateTimeImmutable) - End of date range (inclusive)

**Returns:**
- `array<AttendanceRecordInterface>` - Attendance records within range

**Example:**
```php
$records = $attendanceQuery->findByEmployeeAndDateRange(
    employeeId: 'emp-12345',
    startDate: new \DateTimeImmutable('2024-11-01 00:00:00'),
    endDate: new \DateTimeImmutable('2024-11-30 23:59:59')
);

echo "November attendance: " . count($records) . " records";
```

---

##### `findOpenRecordByEmployee(string $employeeId): ?AttendanceRecordInterface`

Find uncompleted (checked-in but not checked-out) record for an employee.

**Parameters:**
- `$employeeId` (string) - Employee identifier

**Returns:**
- `AttendanceRecordInterface|null` - Open record or null

**Example:**
```php
$openRecord = $attendanceQuery->findOpenRecordByEmployee('emp-12345');

if ($openRecord) {
    echo "Employee is currently checked in since: {$openRecord->getCheckInTime()->format('H:i')}";
}
```

---

### AttendancePersistInterface

**Namespace:** `Nexus\Attendance\Contracts\AttendancePersistInterface`

Write operations for attendance records (CQRS Command Model).

#### Methods

##### `save(AttendanceRecordInterface $record): AttendanceId`

Create or update attendance record.

**Parameters:**
- `$record` (AttendanceRecordInterface) - Attendance record to persist

**Returns:**
- `AttendanceId` - ID of saved record

**Throws:**
- `InvalidAttendanceRecordException` - If record data is invalid

**Example:**
```php
use Nexus\Attendance\Domain\Entities\AttendanceRecord;
use Nexus\Attendance\Domain\ValueObjects\AttendanceId;

$record = new AttendanceRecord(
    id: AttendanceId::generate(),
    employeeId: 'emp-12345',
    tenantId: 'tenant-acme',
    checkInTime: new \DateTimeImmutable(),
    checkOutTime: null,
    checkInCoordinates: ['lat' => 3.1390, 'lng' => 101.6869],
    checkOutCoordinates: null,
    notes: 'Regular check-in'
);

$attendanceId = $attendancePersist->save($record);
echo "Saved with ID: {$attendanceId}";
```

---

##### `delete(AttendanceId $id): void`

Delete attendance record by ID.

**Parameters:**
- `$id` (AttendanceId) - Attendance identifier

**Throws:**
- `AttendanceNotFoundException` - If record not found
- `InvalidAttendanceIdException` - If ID is invalid

**Example:**
```php
$attendancePersist->delete(
    new AttendanceId('01JCQR8X9K4ZMTG1WPBV3NH234')
);
```

---

### WorkScheduleQueryInterface

**Namespace:** `Nexus\Attendance\Contracts\WorkScheduleQueryInterface`

Read-only operations for work schedules.

#### Methods

##### `findById(ScheduleId $id): ?WorkScheduleInterface`

Find work schedule by ID.

**Parameters:**
- `$id` (ScheduleId) - Schedule identifier

**Returns:**
- `WorkScheduleInterface|null` - Work schedule or null

**Example:**
```php
use Nexus\Attendance\Domain\ValueObjects\ScheduleId;

$schedule = $workScheduleQuery->findById(
    new ScheduleId('sch-std-9to6')
);
```

---

##### `findByTenant(string $tenantId): array`

Find all work schedules for a tenant.

**Parameters:**
- `$tenantId` (string) - Tenant identifier

**Returns:**
- `array<WorkScheduleInterface>` - Array of work schedules

**Example:**
```php
$schedules = $workScheduleQuery->findByTenant('tenant-acme');

foreach ($schedules as $schedule) {
    echo "{$schedule->getName()}: {$schedule->getStartTime()->format('H:i')} - {$schedule->getEndTime()->format('H:i')}\n";
}
```

---

##### `findEffectiveSchedule(string $employeeId, \DateTimeImmutable $date): ?WorkScheduleInterface`

Find the effective work schedule for an employee on a specific date.

**Parameters:**
- `$employeeId` (string) - Employee identifier
- `$date` (DateTimeImmutable) - Target date

**Returns:**
- `WorkScheduleInterface|null` - Effective schedule or null

**Example:**
```php
$schedule = $workScheduleQuery->findEffectiveSchedule(
    employeeId: 'emp-12345',
    date: new \DateTimeImmutable('2024-11-15')
);

if ($schedule) {
    echo "Schedule: {$schedule->getName()}";
}
```

---

### WorkSchedulePersistInterface

**Namespace:** `Nexus\Attendance\Contracts\WorkSchedulePersistInterface`

Write operations for work schedules.

#### Methods

##### `save(WorkScheduleInterface $schedule): ScheduleId`

Create or update work schedule.

**Parameters:**
- `$schedule` (WorkScheduleInterface) - Work schedule to persist

**Returns:**
- `ScheduleId` - ID of saved schedule

**Throws:**
- `InvalidWorkScheduleException` - If schedule data is invalid

**Example:**
```php
use Nexus\Attendance\Domain\Entities\WorkSchedule;
use Nexus\Attendance\Domain\ValueObjects\ScheduleId;

$schedule = new WorkSchedule(
    id: ScheduleId::generate(),
    tenantId: 'tenant-acme',
    name: 'Standard 9-to-6',
    startTime: new \DateTimeImmutable('09:00:00'),
    endTime: new \DateTimeImmutable('18:00:00'),
    daysOfWeek: [1, 2, 3, 4, 5],
    graceMinutes: 15,
    effectiveFrom: new \DateTimeImmutable('2024-01-01'),
    effectiveTo: null
);

$scheduleId = $workSchedulePersist->save($schedule);
```

---

##### `delete(ScheduleId $id): void`

Delete work schedule by ID.

**Parameters:**
- `$id` (ScheduleId) - Schedule identifier

**Throws:**
- `WorkScheduleNotFoundException` - If schedule not found

---

### AttendanceManagerInterface

**Namespace:** `Nexus\Attendance\Contracts\AttendanceManagerInterface`

High-level attendance management operations.

#### Methods

##### `checkIn(string $employeeId, string $tenantId, \DateTimeImmutable $checkInTime, ?array $coordinates = null, ?string $notes = null): AttendanceId`

Record employee check-in.

**Parameters:**
- `$employeeId` (string) - Employee identifier
- `$tenantId` (string) - Tenant identifier
- `$checkInTime` (DateTimeImmutable) - Check-in timestamp
- `$coordinates` (array|null) - GPS coordinates ['lat' => float, 'lng' => float]
- `$notes` (string|null) - Optional notes

**Returns:**
- `AttendanceId` - ID of created attendance record

**Throws:**
- `AlreadyCheckedInException` - If employee already has open record
- `InvalidCheckInTimeException` - If check-in time is invalid

**Example:**
```php
use Nexus\Attendance\Exceptions\AlreadyCheckedInException;

try {
    $attendanceId = $attendanceManager->checkIn(
        employeeId: 'emp-12345',
        tenantId: 'tenant-acme',
        checkInTime: new \DateTimeImmutable(),
        coordinates: ['lat' => 3.1390, 'lng' => 101.6869],
        notes: 'Early arrival'
    );
} catch (AlreadyCheckedInException $e) {
    echo "Error: {$e->getMessage()}";
}
```

---

##### `checkOut(AttendanceId $attendanceId, \DateTimeImmutable $checkOutTime, ?array $coordinates = null): void`

Record employee check-out.

**Parameters:**
- `$attendanceId` (AttendanceId) - Attendance record ID
- `$checkOutTime` (DateTimeImmutable) - Check-out timestamp
- `$coordinates` (array|null) - GPS coordinates ['lat' => float, 'lng' => float]

**Throws:**
- `AttendanceNotFoundException` - If record not found
- `AlreadyCheckedOutException` - If already checked out
- `InvalidCheckOutTimeException` - If check-out before check-in

**Example:**
```php
$attendanceManager->checkOut(
    attendanceId: $attendanceId,
    checkOutTime: new \DateTimeImmutable(),
    coordinates: ['lat' => 3.1390, 'lng' => 101.6869]
);
```

---

##### `getAttendance(AttendanceId $id): AttendanceRecordInterface`

Retrieve attendance record by ID.

**Parameters:**
- `$id` (AttendanceId) - Attendance identifier

**Returns:**
- `AttendanceRecordInterface` - Attendance record

**Throws:**
- `AttendanceNotFoundException` - If record not found

---

##### `getAttendanceByEmployee(string $employeeId, \DateTimeImmutable $startDate, \DateTimeImmutable $endDate): array`

Retrieve attendance records for employee within date range.

**Parameters:**
- `$employeeId` (string) - Employee identifier
- `$startDate` (DateTimeImmutable) - Start date (inclusive)
- `$endDate` (DateTimeImmutable) - End date (inclusive)

**Returns:**
- `array<AttendanceRecordInterface>` - Attendance records

---

### WorkScheduleResolverInterface

**Namespace:** `Nexus\Attendance\Contracts\WorkScheduleResolverInterface`

Resolve applicable work schedules for employees.

#### Methods

##### `resolveSchedule(string $employeeId, \DateTimeImmutable $date): ?WorkScheduleInterface`

Determine the applicable work schedule for an employee on a specific date.

**Parameters:**
- `$employeeId` (string) - Employee identifier
- `$date` (DateTimeImmutable) - Target date

**Returns:**
- `WorkScheduleInterface|null` - Applicable schedule or null

**Resolution Logic:**
1. Check if date's day of week is in schedule's `daysOfWeek`
2. Verify date falls within `effectiveFrom` and `effectiveTo`
3. Return matching schedule or null

**Example:**
```php
$schedule = $scheduleResolver->resolveSchedule(
    employeeId: 'emp-12345',
    date: new \DateTimeImmutable('2024-11-15') // Friday
);

if ($schedule && in_array(5, $schedule->getDaysOfWeek())) {
    echo "Employee works Fridays";
}
```

---

### OvertimeCalculatorInterface

**Namespace:** `Nexus\Attendance\Contracts\OvertimeCalculatorInterface`

Calculate overtime hours based on work schedule.

#### Methods

##### `calculate(WorkHours $actualHours, WorkScheduleInterface $schedule): float`

Calculate overtime hours.

**Parameters:**
- `$actualHours` (WorkHours) - Actual hours worked
- `$schedule` (WorkScheduleInterface) - Applicable work schedule

**Returns:**
- `float` - Overtime hours (can be negative if undertime)

**Calculation Logic:**
```
scheduled_hours = schedule.endTime - schedule.startTime
overtime_hours = actual_hours - scheduled_hours
```

**Example:**
```php
use Nexus\Attendance\Domain\ValueObjects\WorkHours;

$overtime = $overtimeCalculator->calculate(
    actualHours: new WorkHours(10.0), // Worked 10 hours
    schedule: $schedule // 9-hour schedule (9 AM - 6 PM)
);

echo "Overtime: {$overtime} hours"; // Output: Overtime: 1 hours
```

---

## Domain Entities

### AttendanceRecord

**Namespace:** `Nexus\Attendance\Domain\Entities\AttendanceRecord`  
**Implements:** `AttendanceRecordInterface`  
**Immutable:** Yes (readonly)

Represents a single attendance entry.

#### Constructor

```php
public function __construct(
    public readonly AttendanceId $id,
    public readonly string $employeeId,
    public readonly string $tenantId,
    public readonly \DateTimeImmutable $checkInTime,
    public readonly ?\DateTimeImmutable $checkOutTime,
    public readonly ?array $checkInCoordinates,
    public readonly ?array $checkOutCoordinates,
    public readonly ?string $notes = null
)
```

#### Methods

##### `getId(): AttendanceId`

Get unique attendance identifier.

---

##### `getEmployeeId(): string`

Get employee identifier.

---

##### `getTenantId(): string`

Get tenant identifier.

---

##### `getCheckInTime(): \DateTimeImmutable`

Get check-in timestamp.

---

##### `getCheckOutTime(): ?\DateTimeImmutable`

Get check-out timestamp (null if still checked in).

---

##### `getCheckInCoordinates(): ?array`

Get check-in GPS coordinates ['lat' => float, 'lng' => float] or null.

---

##### `getCheckOutCoordinates(): ?array`

Get check-out GPS coordinates or null.

---

##### `getNotes(): ?string`

Get optional notes.

---

##### `getWorkHours(): ?WorkHours`

Calculate work hours (null if not checked out).

**Returns:**
- `WorkHours|null` - Duration between check-in and check-out

---

##### `getStatus(): AttendanceStatus`

Get attendance status enum.

**Returns:**
- `AttendanceStatus::CHECKED_IN` - Still working
- `AttendanceStatus::CHECKED_OUT` - Completed

---

##### `withCheckOut(\DateTimeImmutable $checkOutTime, ?array $coordinates): self`

Create new instance with check-out data.

**Parameters:**
- `$checkOutTime` (DateTimeImmutable) - Check-out timestamp
- `$coordinates` (array|null) - GPS coordinates

**Returns:**
- New `AttendanceRecord` instance with check-out data

**Throws:**
- `InvalidCheckOutTimeException` - If check-out before check-in

**Example:**
```php
$record = $attendanceQuery->findById($id);

$updated = $record->withCheckOut(
    new \DateTimeImmutable('2024-11-15 17:30:00'),
    ['lat' => 3.1390, 'lng' => 101.6869]
);

$attendancePersist->save($updated);
```

---

##### `withNotes(string $notes): self`

Create new instance with updated notes.

**Example:**
```php
$updated = $record->withNotes('Left early for medical appointment');
```

---

### WorkSchedule

**Namespace:** `Nexus\Attendance\Domain\Entities\WorkSchedule`  
**Implements:** `WorkScheduleInterface`  
**Immutable:** Yes (readonly)

Represents expected work hours and applicable days.

#### Constructor

```php
public function __construct(
    public readonly ScheduleId $id,
    public readonly string $tenantId,
    public readonly string $name,
    public readonly \DateTimeImmutable $startTime,
    public readonly \DateTimeImmutable $endTime,
    public readonly array $daysOfWeek,
    public readonly int $graceMinutes,
    public readonly \DateTimeImmutable $effectiveFrom,
    public readonly ?\DateTimeImmutable $effectiveTo
)
```

**Constraints:**
- `$daysOfWeek`: Array of integers 1-7 (1=Monday, 7=Sunday)
- `$graceMinutes`: Non-negative integer
- `$endTime` must be after `$startTime`

#### Methods

##### `getId(): ScheduleId`

Get schedule identifier.

---

##### `getTenantId(): string`

Get tenant identifier.

---

##### `getName(): string`

Get schedule name.

---

##### `getStartTime(): \DateTimeImmutable`

Get work start time.

---

##### `getEndTime(): \DateTimeImmutable`

Get work end time.

---

##### `getDaysOfWeek(): array`

Get applicable days of week (1=Monday, 7=Sunday).

**Returns:**
- `array<int>` - Array of day numbers

---

##### `getGraceMinutes(): int`

Get late arrival grace period in minutes.

---

##### `getEffectiveFrom(): \DateTimeImmutable`

Get schedule effective start date.

---

##### `getEffectiveTo(): ?\DateTimeImmutable`

Get schedule effective end date (null = no end).

---

##### `isEffectiveOn(\DateTimeImmutable $date): bool`

Check if schedule is effective on a specific date.

**Parameters:**
- `$date` (DateTimeImmutable) - Target date

**Returns:**
- `bool` - True if effective

**Example:**
```php
$isEffective = $schedule->isEffectiveOn(new \DateTimeImmutable('2024-11-15'));
```

---

##### `appliesToDay(int $dayOfWeek): bool`

Check if schedule applies to a day of week.

**Parameters:**
- `$dayOfWeek` (int) - 1=Monday, 7=Sunday

**Returns:**
- `bool` - True if applicable

---

##### `withEffectiveFrom(\DateTimeImmutable $date): self`

Create new instance with updated effective start date.

---

##### `withEffectiveTo(?\DateTimeImmutable $date): self`

Create new instance with updated effective end date.

---

##### `withGraceMinutes(int $minutes): self`

Create new instance with updated grace period.

---

## Value Objects

### AttendanceId

**Namespace:** `Nexus\Attendance\Domain\ValueObjects\AttendanceId`  
**Immutable:** Yes (readonly)

ULID-based unique identifier for attendance records.

#### Static Methods

##### `AttendanceId::generate(): self`

Generate new ULID.

**Example:**
```php
$id = AttendanceId::generate();
echo $id->toString(); // "01JCQR8X9K4ZMTG1WPBV3NH234"
```

---

##### `AttendanceId::fromString(string $id): self`

Create from existing ULID string.

**Throws:**
- `InvalidAttendanceIdException` - If format invalid

---

#### Methods

##### `toString(): string`

Get ULID as string.

---

##### `equals(AttendanceId $other): bool`

Compare with another ID.

---

### ScheduleId

**Namespace:** `Nexus\Attendance\Domain\ValueObjects\ScheduleId`  
**Immutable:** Yes (readonly)

ULID-based unique identifier for work schedules.

Same API as `AttendanceId`.

---

### WorkHours

**Namespace:** `Nexus\Attendance\Domain\ValueObjects\WorkHours`  
**Immutable:** Yes (readonly)

Represents duration of work in hours.

#### Constructor

```php
public function __construct(
    public readonly float $hours
)
```

**Constraints:**
- `$hours` must be >= 0

#### Static Methods

##### `WorkHours::fromMinutes(int $minutes): self`

Create from minutes.

**Example:**
```php
$hours = WorkHours::fromMinutes(480); // 8 hours
```

---

##### `WorkHours::fromSeconds(int $seconds): self`

Create from seconds.

---

##### `WorkHours::fromInterval(\DateTimeImmutable $start, \DateTimeImmutable $end): self`

Calculate from time interval.

**Example:**
```php
$hours = WorkHours::fromInterval(
    new \DateTimeImmutable('2024-11-15 08:00:00'),
    new \DateTimeImmutable('2024-11-15 17:30:00')
);

echo $hours->getHours(); // 9.5
```

---

#### Methods

##### `getHours(): float`

Get hours as float.

---

##### `getMinutes(): int`

Get total minutes.

---

##### `format(): string`

Format as "HH:MM" string.

**Example:**
```php
$hours = new WorkHours(9.5);
echo $hours->format(); // "09:30"
```

---

## Domain Services

### AttendanceManager

**Namespace:** `Nexus\Attendance\Services\AttendanceManager`  
**Implements:** `AttendanceManagerInterface`

High-level facade for attendance operations.

See [AttendanceManagerInterface](#attendancemanagerinterface) for method documentation.

---

### WorkScheduleResolver

**Namespace:** `Nexus\Attendance\Services\WorkScheduleResolver`  
**Implements:** `WorkScheduleResolverInterface`

Resolves applicable work schedules using multi-criteria validation.

See [WorkScheduleResolverInterface](#workscheduleresolverinterface) for method documentation.

---

### OvertimeCalculator

**Namespace:** `Nexus\Attendance\Services\OvertimeCalculator`  
**Implements:** `OvertimeCalculatorInterface`

Calculates overtime based on schedule comparison.

See [OvertimeCalculatorInterface](#overtimecalculatorinterface) for method documentation.

---

## Enumerations

### AttendanceStatus

**Namespace:** `Nexus\Attendance\Domain\Enums\AttendanceStatus`  
**Type:** Backed enum (string)

Attendance record status.

#### Cases

##### `AttendanceStatus::CHECKED_IN`
**Value:** `"checked_in"`  
Employee has checked in but not yet checked out.

##### `AttendanceStatus::CHECKED_OUT`
**Value:** `"checked_out"`  
Employee has completed check-in/check-out cycle.

**Example:**
```php
if ($record->getStatus() === AttendanceStatus::CHECKED_IN) {
    echo "Employee is currently working";
}
```

---

### CheckType

**Namespace:** `Nexus\Attendance\Domain\Enums\CheckType`  
**Type:** Backed enum (string)

Type of attendance check action.

#### Cases

##### `CheckType::IN`
**Value:** `"in"`  
Check-in action.

##### `CheckType::OUT`
**Value:** `"out"`  
Check-out action.

---

## Exceptions

All exceptions use **static factory methods** for consistency.

### AttendanceNotFoundException

**Namespace:** `Nexus\Attendance\Exceptions\AttendanceNotFoundException`  
**Extends:** `RuntimeException`

Thrown when attendance record not found.

#### Static Factory

##### `AttendanceNotFoundException::withId(AttendanceId $id): self`

**Example:**
```php
use Nexus\Attendance\Exceptions\AttendanceNotFoundException;

throw AttendanceNotFoundException::withId($attendanceId);
// Message: "Attendance record with ID '01JCQR...' not found."
```

---

### AlreadyCheckedInException

**Namespace:** `Nexus\Attendance\Exceptions\AlreadyCheckedInException`  
**Extends:** `RuntimeException`

Thrown when employee already has open record.

#### Static Factory

##### `AlreadyCheckedInException::forEmployee(string $employeeId, AttendanceId $existingId): self`

**Example:**
```php
throw AlreadyCheckedInException::forEmployee('emp-12345', $existingId);
// Message: "Employee 'emp-12345' already checked in (record ID: 01JCQR...)."
```

---

### AlreadyCheckedOutException

**Namespace:** `Nexus\Attendance\Exceptions\AlreadyCheckedOutException`  
**Extends:** `RuntimeException`

Thrown when attempting to check out already completed record.

#### Static Factory

##### `AlreadyCheckedOutException::forRecord(AttendanceId $id): self`

**Example:**
```php
throw AlreadyCheckedOutException::forRecord($attendanceId);
// Message: "Attendance record '01JCQR...' is already checked out."
```

---

### InvalidCheckOutTimeException

**Namespace:** `Nexus\Attendance\Exceptions\InvalidCheckOutTimeException`  
**Extends:** `RuntimeException`

Thrown when check-out time is before check-in time.

#### Static Factory

##### `InvalidCheckOutTimeException::beforeCheckIn(\DateTimeImmutable $checkIn, \DateTimeImmutable $checkOut): self`

**Example:**
```php
throw InvalidCheckOutTimeException::beforeCheckIn(
    $record->getCheckInTime(),
    $checkOutTime
);
// Message: "Check-out time '2024-11-15 08:00:00' cannot be before check-in time '2024-11-15 09:00:00'."
```

---

### InvalidWorkScheduleException

**Namespace:** `Nexus\Attendance\Exceptions\InvalidWorkScheduleException`  
**Extends:** `RuntimeException`

Thrown when work schedule data is invalid.

#### Static Factories

##### `InvalidWorkScheduleException::endBeforeStart(\DateTimeImmutable $start, \DateTimeImmutable $end): self`

**Example:**
```php
throw InvalidWorkScheduleException::endBeforeStart($startTime, $endTime);
// Message: "Schedule end time '08:00:00' cannot be before start time '18:00:00'."
```

##### `InvalidWorkScheduleException::invalidDaysOfWeek(array $days): self`

**Example:**
```php
throw InvalidWorkScheduleException::invalidDaysOfWeek([0, 8]);
// Message: "Invalid days of week: [0, 8]. Must be integers 1-7."
```

---

## Usage Examples

### Complete Workflow Example

```php
use Nexus\Attendance\Services\AttendanceManager;
use Nexus\Attendance\Services\OvertimeCalculator;
use Nexus\Attendance\Services\WorkScheduleResolver;
use Nexus\Attendance\Domain\Entities\WorkSchedule;
use Nexus\Attendance\Domain\ValueObjects\ScheduleId;
use Nexus\Attendance\Exceptions\AlreadyCheckedInException;

class AttendanceService
{
    public function __construct(
        private readonly AttendanceManager $attendanceManager,
        private readonly WorkScheduleResolver $scheduleResolver,
        private readonly OvertimeCalculator $overtimeCalc
    ) {}
    
    public function employeeCheckIn(string $employeeId): array
    {
        try {
            $attendanceId = $this->attendanceManager->checkIn(
                employeeId: $employeeId,
                tenantId: 'tenant-acme',
                checkInTime: new \DateTimeImmutable(),
                coordinates: ['lat' => 3.1390, 'lng' => 101.6869]
            );
            
            return ['success' => true, 'attendance_id' => $attendanceId->toString()];
            
        } catch (AlreadyCheckedInException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    public function employeeCheckOut(string $attendanceId): array
    {
        try {
            $this->attendanceManager->checkOut(
                attendanceId: AttendanceId::fromString($attendanceId),
                checkOutTime: new \DateTimeImmutable(),
                coordinates: ['lat' => 3.1390, 'lng' => 101.6869]
            );
            
            // Calculate overtime
            $record = $this->attendanceManager->getAttendance(
                AttendanceId::fromString($attendanceId)
            );
            
            $schedule = $this->scheduleResolver->resolveSchedule(
                $record->getEmployeeId(),
                $record->getCheckInTime()
            );
            
            $overtime = 0.0;
            if ($schedule && $record->getWorkHours()) {
                $overtime = $this->overtimeCalc->calculate(
                    $record->getWorkHours(),
                    $schedule
                );
            }
            
            return [
                'success' => true,
                'work_hours' => $record->getWorkHours()?->getHours(),
                'overtime_hours' => $overtime
            ];
            
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
```

---

## See Also

- **[Getting Started Guide](getting-started.md)** - Quick start and basic concepts
- **[Integration Guide](integration-guide.md)** - Framework-specific integration
- **[Examples](examples/)** - Runnable code samples
- **[Package Requirements](../REQUIREMENTS.md)** - Detailed requirements (45 items)
- **[Test Suite Summary](../TEST_SUITE_SUMMARY.md)** - 46 tests, 100% coverage

---

**For questions or issues, see the [GitHub repository](https://github.com/nexus/attendance-management).**
