<?php

/**
 * Basic Usage Example: Nexus Attendance
 * 
 * This file demonstrates simple, day-to-day usage patterns for the
 * Attendance package including check-in, check-out, and retrieving records.
 * 
 * Prerequisites:
 * - Package installed via Composer
 * - Framework container configured (Laravel/Symfony service provider)
 * - Database migrations run
 * 
 * @package Nexus\Attendance
 * @version 1.0.0
 */

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

use Nexus\Attendance\Contracts\AttendanceManagerInterface;
use Nexus\Attendance\Contracts\AttendanceQueryInterface;
use Nexus\Attendance\Contracts\WorkSchedulePersistInterface;
use Nexus\Attendance\Domain\Entities\WorkSchedule;
use Nexus\Attendance\Domain\ValueObjects\AttendanceId;
use Nexus\Attendance\Domain\ValueObjects\ScheduleId;
use Nexus\Attendance\Exceptions\AlreadyCheckedInException;
use Nexus\Attendance\Exceptions\AttendanceNotFoundException;
use Psr\Log\NullLogger;

// ============================================================================
// EXAMPLE 1: Create Work Schedule
// ============================================================================

echo "=== EXAMPLE 1: Create Work Schedule ===\n\n";

/**
 * Create a standard 9-to-6 work schedule for Monday-Friday.
 */
function createWorkSchedule(WorkSchedulePersistInterface $schedulePersist): ScheduleId
{
    $schedule = new WorkSchedule(
        id: ScheduleId::generate(),
        tenantId: 'tenant-demo',
        name: 'Standard Office Hours',
        startTime: new \DateTimeImmutable('09:00:00'),
        endTime: new \DateTimeImmutable('18:00:00'),
        daysOfWeek: [1, 2, 3, 4, 5], // Monday=1, Friday=5
        graceMinutes: 15, // 15-minute late arrival tolerance
        effectiveFrom: new \DateTimeImmutable('2024-01-01'),
        effectiveTo: null // No end date
    );

    $scheduleId = $schedulePersist->save($schedule);

    echo "✓ Work schedule created: {$scheduleId->toString()}\n";
    echo "  Name: {$schedule->getName()}\n";
    echo "  Hours: {$schedule->getStartTime()->format('H:i')} - {$schedule->getEndTime()->format('H:i')}\n";
    echo "  Days: Monday to Friday\n";
    echo "  Grace Period: 15 minutes\n\n";

    return $scheduleId;
}

// ============================================================================
// EXAMPLE 2: Employee Check-In
// ============================================================================

echo "=== EXAMPLE 2: Employee Check-In ===\n\n";

/**
 * Record employee check-in with GPS coordinates.
 */
function employeeCheckIn(
    AttendanceManagerInterface $attendanceManager,
    string $employeeId
): ?AttendanceId {
    try {
        $attendanceId = $attendanceManager->checkIn(
            employeeId: $employeeId,
            tenantId: 'tenant-demo',
            checkInTime: new \DateTimeImmutable(),
            coordinates: [
                'lat' => 3.1390, // Kuala Lumpur coordinates
                'lng' => 101.6869,
            ],
            notes: 'On-time arrival'
        );

        echo "✓ Employee {$employeeId} checked in successfully!\n";
        echo "  Attendance ID: {$attendanceId->toString()}\n";
        echo "  Check-in time: " . (new \DateTimeImmutable())->format('Y-m-d H:i:s') . "\n\n";

        return $attendanceId;

    } catch (AlreadyCheckedInException $e) {
        echo "✗ Check-in failed: {$e->getMessage()}\n\n";
        return null;
    }
}

// ============================================================================
// EXAMPLE 3: Employee Check-Out
// ============================================================================

echo "=== EXAMPLE 3: Employee Check-Out ===\n\n";

/**
 * Record employee check-out.
 */
function employeeCheckOut(
    AttendanceManagerInterface $attendanceManager,
    AttendanceId $attendanceId
): void {
    try {
        $attendanceManager->checkOut(
            attendanceId: $attendanceId,
            checkOutTime: new \DateTimeImmutable(),
            coordinates: [
                'lat' => 3.1390,
                'lng' => 101.6869,
            ]
        );

        $record = $attendanceManager->getAttendance($attendanceId);

        echo "✓ Employee checked out successfully!\n";
        echo "  Check-out time: {$record->getCheckOutTime()->format('Y-m-d H:i:s')}\n";
        echo "  Total work hours: {$record->getWorkHours()?->format()}\n";
        echo "  Status: {$record->getStatus()->value}\n\n";

    } catch (AttendanceNotFoundException $e) {
        echo "✗ Check-out failed: {$e->getMessage()}\n\n";
    }
}

// ============================================================================
// EXAMPLE 4: Retrieve Attendance Record
// ============================================================================

echo "=== EXAMPLE 4: Retrieve Attendance Record ===\n\n";

/**
 * Retrieve and display attendance record details.
 */
function displayAttendanceRecord(
    AttendanceManagerInterface $attendanceManager,
    AttendanceId $attendanceId
): void {
    try {
        $record = $attendanceManager->getAttendance($attendanceId);

        echo "Attendance Record Details:\n";
        echo "  ID: {$record->getId()->toString()}\n";
        echo "  Employee: {$record->getEmployeeId()}\n";
        echo "  Tenant: {$record->getTenantId()}\n";
        echo "  Check-in: {$record->getCheckInTime()->format('Y-m-d H:i:s')}\n";
        
        if ($record->getCheckOutTime()) {
            echo "  Check-out: {$record->getCheckOutTime()->format('Y-m-d H:i:s')}\n";
            echo "  Work hours: {$record->getWorkHours()?->format()}\n";
        } else {
            echo "  Check-out: Not yet checked out\n";
        }

        echo "  Status: {$record->getStatus()->value}\n";

        if ($record->getNotes()) {
            echo "  Notes: {$record->getNotes()}\n";
        }

        echo "\n";

    } catch (AttendanceNotFoundException $e) {
        echo "✗ Record not found: {$e->getMessage()}\n\n";
    }
}

// ============================================================================
// EXAMPLE 5: Query Attendance History
// ============================================================================

echo "=== EXAMPLE 5: Query Attendance History ===\n\n";

/**
 * Retrieve attendance history for an employee within a date range.
 */
function displayAttendanceHistory(
    AttendanceQueryInterface $attendanceQuery,
    string $employeeId,
    \DateTimeImmutable $startDate,
    \DateTimeImmutable $endDate
): void {
    $records = $attendanceQuery->findByEmployeeAndDateRange(
        employeeId: $employeeId,
        startDate: $startDate,
        endDate: $endDate
    );

    echo "Attendance History for Employee: {$employeeId}\n";
    echo "Date Range: {$startDate->format('Y-m-d')} to {$endDate->format('Y-m-d')}\n";
    echo "Total Records: " . count($records) . "\n\n";

    if (empty($records)) {
        echo "No attendance records found.\n\n";
        return;
    }

    foreach ($records as $record) {
        echo "  Date: {$record->getCheckInTime()->format('Y-m-d')}\n";
        echo "  Check-in: {$record->getCheckInTime()->format('H:i:s')}\n";
        
        if ($record->getCheckOutTime()) {
            echo "  Check-out: {$record->getCheckOutTime()->format('H:i:s')}\n";
            echo "  Hours: {$record->getWorkHours()?->format()}\n";
        } else {
            echo "  Check-out: Still working\n";
        }
        
        echo "  ---\n";
    }

    echo "\n";
}

// ============================================================================
// EXAMPLE 6: Calculate Daily Work Hours
// ============================================================================

echo "=== EXAMPLE 6: Calculate Daily Work Hours ===\n\n";

/**
 * Calculate total work hours for a specific day.
 */
function calculateDailyHours(
    AttendanceQueryInterface $attendanceQuery,
    string $employeeId,
    \DateTimeImmutable $date
): float {
    $records = $attendanceQuery->findByEmployeeAndDateRange(
        employeeId: $employeeId,
        startDate: $date->setTime(0, 0, 0),
        endDate: $date->setTime(23, 59, 59)
    );

    $totalHours = 0.0;

    foreach ($records as $record) {
        if ($record->getWorkHours()) {
            $totalHours += $record->getWorkHours()->getHours();
        }
    }

    echo "Daily Work Hours for {$employeeId} on {$date->format('Y-m-d')}:\n";
    echo "  Total: {$totalHours} hours\n";
    echo "  Formatted: " . floor($totalHours) . "h " . round(($totalHours - floor($totalHours)) * 60) . "m\n\n";

    return $totalHours;
}

// ============================================================================
// EXAMPLE 7: Check Open Records (Currently Checked In)
// ============================================================================

echo "=== EXAMPLE 7: Check Open Records ===\n\n";

/**
 * Check if employee has an open (uncompleted) attendance record.
 */
function checkOpenRecord(
    AttendanceQueryInterface $attendanceQuery,
    string $employeeId
): void {
    $openRecord = $attendanceQuery->findOpenRecordByEmployee($employeeId);

    if ($openRecord) {
        $duration = (new \DateTimeImmutable())->getTimestamp() - $openRecord->getCheckInTime()->getTimestamp();
        $hours = floor($duration / 3600);
        $minutes = floor(($duration % 3600) / 60);

        echo "✓ Employee {$employeeId} is currently checked in\n";
        echo "  Check-in time: {$openRecord->getCheckInTime()->format('Y-m-d H:i:s')}\n";
        echo "  Duration: {$hours}h {$minutes}m\n\n";
    } else {
        echo "✓ Employee {$employeeId} has no open records\n";
        echo "  (Not currently checked in)\n\n";
    }
}

// ============================================================================
// EXAMPLE 8: Modify Attendance Record (Immutable Pattern)
// ============================================================================

echo "=== EXAMPLE 8: Modify Attendance Record ===\n\n";

/**
 * Demonstrate immutable entity modification using "with*" methods.
 */
function updateAttendanceNotes(
    AttendanceManagerInterface $attendanceManager,
    AttendanceQueryInterface $attendanceQuery,
    AttendancePersistInterface $attendancePersist,
    AttendanceId $attendanceId
): void {
    // Retrieve original record
    $record = $attendanceQuery->findById($attendanceId);

    if (!$record) {
        echo "✗ Record not found\n\n";
        return;
    }

    echo "Original notes: " . ($record->getNotes() ?? '(none)') . "\n";

    // Create new instance with updated notes (immutability)
    $updatedRecord = $record->withNotes('Updated: Left early for medical appointment');

    // Save updated record
    $attendancePersist->save($updatedRecord);

    // Verify update
    $verifyRecord = $attendanceQuery->findById($attendanceId);
    echo "Updated notes: {$verifyRecord->getNotes()}\n\n";
}

// ============================================================================
// RUNNING EXAMPLES (Pseudo-code - requires actual DI container)
// ============================================================================

echo "\n=== DEMO COMPLETE ===\n";
echo "To run these examples in your application:\n";
echo "1. Inject services via your framework's DI container (Laravel/Symfony)\n";
echo "2. Ensure database migrations are run\n";
echo "3. Replace 'tenant-demo' and 'emp-12345' with actual IDs\n";
echo "4. Call functions with injected dependencies\n\n";

echo "Example usage in Laravel controller:\n\n";
echo <<<'PHP'
public function __construct(
    private readonly AttendanceManagerInterface $attendanceManager,
    private readonly AttendanceQueryInterface $attendanceQuery
) {}

public function checkIn(Request $request)
{
    $attendanceId = employeeCheckIn(
        $this->attendanceManager,
        $request->user()->employee_id
    );
    
    return response()->json([
        'success' => true,
        'attendance_id' => $attendanceId?->toString()
    ]);
}
PHP;

echo "\n\n";
