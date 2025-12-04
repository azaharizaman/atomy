<?php

/**
 * Advanced Usage Example: Nexus Attendance
 * 
 * This file demonstrates complex scenarios including:
 * - Multi-day attendance aggregation
 * - Late arrival detection with grace periods
 * - Overtime calculation with schedule resolution
 * - Work schedule assignment to employees
 * - Edge case handling (duplicates, missing check-outs, schedule gaps)
 * 
 * Prerequisites:
 * - Package installed via Composer
 * - Framework container configured
 * - Database migrations run
 * - Work schedules created
 * 
 * @package Nexus\Attendance
 * @version 1.0.0
 */

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

use Nexus\Attendance\Contracts\AttendanceManagerInterface;
use Nexus\Attendance\Contracts\AttendanceQueryInterface;
use Nexus\Attendance\Contracts\WorkScheduleResolverInterface;
use Nexus\Attendance\Contracts\OvertimeCalculatorInterface;
use Nexus\Attendance\Domain\ValueObjects\AttendanceId;
use Nexus\Attendance\Exceptions\AlreadyCheckedInException;
use Nexus\Attendance\Exceptions\InvalidCheckOutTimeException;

// ============================================================================
// EXAMPLE 1: Multi-Day Attendance Aggregation
// ============================================================================

echo "=== EXAMPLE 1: Multi-Day Attendance Aggregation ===\n\n";

/**
 * Calculate total work hours across multiple days.
 */
function calculateWeeklyHours(
    AttendanceQueryInterface $attendanceQuery,
    string $employeeId,
    string $tenantId
): array {
    // Get current week's Monday and Sunday
    $monday = new \DateTimeImmutable('monday this week');
    $sunday = new \DateTimeImmutable('sunday this week');

    $records = $attendanceQuery->findByEmployeeAndDateRange(
        employeeId: $employeeId,
        startDate: $monday->setTime(0, 0, 0),
        endDate: $sunday->setTime(23, 59, 59)
    );

    $dailyHours = [];
    $totalHours = 0.0;
    $workingDays = 0;

    foreach ($records as $record) {
        if ($record->getWorkHours()) {
            $date = $record->getCheckInTime()->format('Y-m-d');
            
            if (!isset($dailyHours[$date])) {
                $dailyHours[$date] = 0.0;
                $workingDays++;
            }

            $hours = $record->getWorkHours()->getHours();
            $dailyHours[$date] += $hours;
            $totalHours += $hours;
        }
    }

    echo "Weekly Summary for {$employeeId}:\n";
    echo "  Period: {$monday->format('Y-m-d')} to {$sunday->format('Y-m-d')}\n";
    echo "  Working Days: {$workingDays}\n";
    echo "  Total Hours: " . round($totalHours, 2) . "h\n";
    echo "  Average Hours/Day: " . ($workingDays > 0 ? round($totalHours / $workingDays, 2) : 0) . "h\n\n";

    foreach ($dailyHours as $date => $hours) {
        echo "    {$date}: " . round($hours, 2) . "h\n";
    }

    echo "\n";

    return [
        'total_hours' => $totalHours,
        'working_days' => $workingDays,
        'daily_breakdown' => $dailyHours,
    ];
}

// ============================================================================
// EXAMPLE 2: Late Arrival Detection
// ============================================================================

echo "=== EXAMPLE 2: Late Arrival Detection ===\n\n";

/**
 * Detect late arrivals considering grace periods.
 */
function detectLateArrivals(
    AttendanceQueryInterface $attendanceQuery,
    WorkScheduleResolverInterface $scheduleResolver,
    string $employeeId,
    \DateTimeImmutable $date
): array {
    // Get attendance records for the day
    $records = $attendanceQuery->findByEmployeeAndDateRange(
        employeeId: $employeeId,
        startDate: $date->setTime(0, 0, 0),
        endDate: $date->setTime(23, 59, 59)
    );

    if (empty($records)) {
        echo "No attendance records found for {$date->format('Y-m-d')}\n\n";
        return [];
    }

    $lateArrivals = [];

    foreach ($records as $record) {
        // Resolve applicable work schedule
        $schedule = $scheduleResolver->resolveSchedule(
            employeeId: $employeeId,
            date: $record->getCheckInTime()
        );

        if (!$schedule) {
            continue;
        }

        $checkInTime = $record->getCheckInTime();
        $scheduledStart = $schedule->getStartTime();
        $graceMinutes = $schedule->getGraceMinutes();

        // Create comparable times (same date, different times)
        $checkInSeconds = (int) $checkInTime->format('H') * 3600 + 
                         (int) $checkInTime->format('i') * 60 + 
                         (int) $checkInTime->format('s');
        $scheduledSeconds = (int) $scheduledStart->format('H') * 3600 + 
                           (int) $scheduledStart->format('i') * 60 + 
                           (int) $scheduledStart->format('s');
        $graceSeconds = $graceMinutes * 60;

        $lateSeconds = $checkInSeconds - ($scheduledSeconds + $graceSeconds);

        if ($lateSeconds > 0) {
            $lateMinutes = (int) ceil($lateSeconds / 60);
            
            $lateArrivals[] = [
                'date' => $checkInTime->format('Y-m-d'),
                'scheduled_time' => $scheduledStart->format('H:i'),
                'actual_time' => $checkInTime->format('H:i'),
                'grace_minutes' => $graceMinutes,
                'late_minutes' => $lateMinutes,
            ];

            echo "Late Arrival Detected:\n";
            echo "  Date: {$checkInTime->format('Y-m-d')}\n";
            echo "  Scheduled: {$scheduledStart->format('H:i')}\n";
            echo "  Grace Period: {$graceMinutes} minutes\n";
            echo "  Actual: {$checkInTime->format('H:i')}\n";
            echo "  Late By: {$lateMinutes} minutes\n\n";
        }
    }

    if (empty($lateArrivals)) {
        echo "✓ No late arrivals detected\n\n";
    }

    return $lateArrivals;
}

// ============================================================================
// EXAMPLE 3: Overtime Calculation
// ============================================================================

echo "=== EXAMPLE 3: Overtime Calculation ===\n\n";

/**
 * Calculate overtime hours for an employee.
 */
function calculateOvertimeHours(
    AttendanceQueryInterface $attendanceQuery,
    OvertimeCalculatorInterface $overtimeCalculator,
    WorkScheduleResolverInterface $scheduleResolver,
    string $employeeId,
    \DateTimeImmutable $date
): ?array {
    // Get attendance records
    $records = $attendanceQuery->findByEmployeeAndDateRange(
        employeeId: $employeeId,
        startDate: $date->setTime(0, 0, 0),
        endDate: $date->setTime(23, 59, 59)
    );

    if (empty($records)) {
        echo "No attendance records found\n\n";
        return null;
    }

    $record = $records[0]; // Use first record for the day

    if (!$record->getWorkHours()) {
        echo "Employee has not checked out yet\n\n";
        return null;
    }

    // Resolve work schedule
    $schedule = $scheduleResolver->resolveSchedule(
        employeeId: $employeeId,
        date: $date
    );

    if (!$schedule) {
        echo "No work schedule found for this employee\n\n";
        return null;
    }

    // Calculate overtime
    $overtimeHours = $overtimeCalculator->calculate(
        attendance: $record,
        schedule: $schedule
    );

    $regularHours = $record->getWorkHours()->getHours() - $overtimeHours->getHours();

    echo "Overtime Calculation for {$date->format('Y-m-d')}:\n";
    echo "  Employee: {$employeeId}\n";
    echo "  Scheduled Hours: {$schedule->getStartTime()->format('H:i')} - {$schedule->getEndTime()->format('H:i')}\n";
    echo "  Actual Hours: {$record->getCheckInTime()->format('H:i')} - {$record->getCheckOutTime()->format('H:i')}\n";
    echo "  Total Worked: {$record->getWorkHours()->format()}\n";
    echo "  Regular Hours: " . round($regularHours, 2) . "h\n";
    echo "  Overtime Hours: {$overtimeHours->format()}\n\n";

    return [
        'total_hours' => $record->getWorkHours()->getHours(),
        'regular_hours' => $regularHours,
        'overtime_hours' => $overtimeHours->getHours(),
    ];
}

// ============================================================================
// EXAMPLE 4: Handle Duplicate Check-In Attempt
// ============================================================================

echo "=== EXAMPLE 4: Handle Duplicate Check-In ===\n\n";

/**
 * Gracefully handle duplicate check-in attempts.
 */
function safeCheckIn(
    AttendanceManagerInterface $attendanceManager,
    AttendanceQueryInterface $attendanceQuery,
    string $employeeId,
    string $tenantId
): ?AttendanceId {
    try {
        // Check for existing open record first
        $existingRecord = $attendanceQuery->findOpenRecordByEmployee($employeeId);

        if ($existingRecord) {
            echo "⚠ Employee already checked in\n";
            echo "  Existing record: {$existingRecord->getId()->toString()}\n";
            echo "  Check-in time: {$existingRecord->getCheckInTime()->format('Y-m-d H:i:s')}\n";
            echo "  Duration: " . calculateDuration($existingRecord->getCheckInTime()) . "\n\n";
            
            return $existingRecord->getId();
        }

        // Attempt check-in
        $attendanceId = $attendanceManager->checkIn(
            employeeId: $employeeId,
            tenantId: $tenantId,
            checkInTime: new \DateTimeImmutable(),
            coordinates: ['lat' => 3.1390, 'lng' => 101.6869]
        );

        echo "✓ Check-in successful: {$attendanceId->toString()}\n\n";
        return $attendanceId;

    } catch (AlreadyCheckedInException $e) {
        echo "✗ Check-in failed: {$e->getMessage()}\n\n";
        return null;
    }
}

/**
 * Helper: Calculate duration from timestamp to now.
 */
function calculateDuration(\DateTimeImmutable $from): string
{
    $now = new \DateTimeImmutable();
    $interval = $from->diff($now);
    
    return sprintf('%dh %dm', $interval->h + ($interval->days * 24), $interval->i);
}

// ============================================================================
// EXAMPLE 5: Automatic Check-Out for Missing Records
// ============================================================================

echo "=== EXAMPLE 5: Auto Check-Out Missing Records ===\n\n";

/**
 * Find and auto-complete records where employee forgot to check out.
 */
function autoCheckOutMissingRecords(
    AttendanceQueryInterface $attendanceQuery,
    AttendanceManagerInterface $attendanceManager,
    WorkScheduleResolverInterface $scheduleResolver,
    string $employeeId,
    \DateTimeImmutable $date
): int {
    $records = $attendanceQuery->findByEmployeeAndDateRange(
        employeeId: $employeeId,
        startDate: $date->setTime(0, 0, 0),
        endDate: $date->setTime(23, 59, 59)
    );

    $autoCompletedCount = 0;

    foreach ($records as $record) {
        // Skip already completed records
        if ($record->getCheckOutTime()) {
            continue;
        }

        // Resolve schedule
        $schedule = $scheduleResolver->resolveSchedule(
            employeeId: $employeeId,
            date: $record->getCheckInTime()
        );

        if (!$schedule) {
            continue;
        }

        // Auto check-out at scheduled end time
        $autoCheckOutTime = $record->getCheckInTime()->setTime(
            (int) $schedule->getEndTime()->format('H'),
            (int) $schedule->getEndTime()->format('i'),
            (int) $schedule->getEndTime()->format('s')
        );

        try {
            $attendanceManager->checkOut(
                attendanceId: $record->getId(),
                checkOutTime: $autoCheckOutTime
            );

            echo "✓ Auto-completed record: {$record->getId()->toString()}\n";
            echo "  Check-in: {$record->getCheckInTime()->format('H:i:s')}\n";
            echo "  Auto check-out: {$autoCheckOutTime->format('H:i:s')}\n\n";

            $autoCompletedCount++;

        } catch (InvalidCheckOutTimeException $e) {
            echo "✗ Failed to auto-complete: {$e->getMessage()}\n\n";
        }
    }

    if ($autoCompletedCount === 0) {
        echo "No records required auto-completion\n\n";
    }

    return $autoCompletedCount;
}

// ============================================================================
// EXAMPLE 6: Bulk Check-In Processing
// ============================================================================

echo "=== EXAMPLE 6: Bulk Check-In Processing ===\n\n";

/**
 * Process bulk check-ins from CSV import or batch system.
 */
function processBulkCheckIns(
    AttendanceManagerInterface $attendanceManager,
    array $checkInData
): array {
    $results = [
        'success' => 0,
        'failed' => 0,
        'errors' => [],
    ];

    foreach ($checkInData as $index => $data) {
        try {
            $attendanceId = $attendanceManager->checkIn(
                employeeId: $data['employee_id'],
                tenantId: $data['tenant_id'],
                checkInTime: new \DateTimeImmutable($data['check_in_time']),
                coordinates: $data['coordinates'] ?? null,
                notes: $data['notes'] ?? null
            );

            $results['success']++;
            echo "✓ [{$index}] Employee {$data['employee_id']}: {$attendanceId->toString()}\n";

        } catch (\Exception $e) {
            $results['failed']++;
            $results['errors'][] = [
                'index' => $index,
                'employee_id' => $data['employee_id'],
                'error' => $e->getMessage(),
            ];
            echo "✗ [{$index}] Employee {$data['employee_id']}: {$e->getMessage()}\n";
        }
    }

    echo "\nBulk Processing Complete:\n";
    echo "  Success: {$results['success']}\n";
    echo "  Failed: {$results['failed']}\n\n";

    return $results;
}

// ============================================================================
// EXAMPLE 7: Attendance Compliance Report
// ============================================================================

echo "=== EXAMPLE 7: Attendance Compliance Report ===\n\n";

/**
 * Generate compliance report showing attendance vs required days.
 */
function generateComplianceReport(
    AttendanceQueryInterface $attendanceQuery,
    WorkScheduleResolverInterface $scheduleResolver,
    string $employeeId,
    \DateTimeImmutable $startDate,
    \DateTimeImmutable $endDate
): array {
    $records = $attendanceQuery->findByEmployeeAndDateRange(
        employeeId: $employeeId,
        startDate: $startDate,
        endDate: $endDate
    );

    $attendedDays = [];
    foreach ($records as $record) {
        $date = $record->getCheckInTime()->format('Y-m-d');
        $attendedDays[$date] = true;
    }

    // Calculate expected working days
    $expectedDays = 0;
    $currentDate = $startDate;
    $scheduledDays = [];

    while ($currentDate <= $endDate) {
        $schedule = $scheduleResolver->resolveSchedule($employeeId, $currentDate);
        
        if ($schedule && $schedule->appliesToDay((int) $currentDate->format('N'))) {
            $expectedDays++;
            $scheduledDays[] = $currentDate->format('Y-m-d');
        }

        $currentDate = $currentDate->modify('+1 day');
    }

    $attendanceRate = $expectedDays > 0 
        ? round((count($attendedDays) / $expectedDays) * 100, 2) 
        : 0;

    echo "Attendance Compliance Report:\n";
    echo "  Employee: {$employeeId}\n";
    echo "  Period: {$startDate->format('Y-m-d')} to {$endDate->format('Y-m-d')}\n";
    echo "  Expected Days: {$expectedDays}\n";
    echo "  Attended Days: " . count($attendedDays) . "\n";
    echo "  Attendance Rate: {$attendanceRate}%\n\n";

    // Show missing days
    $missingDays = array_diff($scheduledDays, array_keys($attendedDays));
    if (!empty($missingDays)) {
        echo "  Missing Days:\n";
        foreach ($missingDays as $day) {
            echo "    - {$day}\n";
        }
    }

    echo "\n";

    return [
        'expected_days' => $expectedDays,
        'attended_days' => count($attendedDays),
        'attendance_rate' => $attendanceRate,
        'missing_days' => $missingDays,
    ];
}

// ============================================================================
// DEMO DATA FOR EXAMPLES
// ============================================================================

echo "\n=== DEMO DATA ===\n\n";

// Sample bulk check-in data
$sampleBulkData = [
    [
        'employee_id' => 'emp-001',
        'tenant_id' => 'tenant-demo',
        'check_in_time' => '2024-01-15 08:45:00',
        'coordinates' => ['lat' => 3.1390, 'lng' => 101.6869],
        'notes' => 'On time',
    ],
    [
        'employee_id' => 'emp-002',
        'tenant_id' => 'tenant-demo',
        'check_in_time' => '2024-01-15 09:20:00',
        'coordinates' => ['lat' => 3.1390, 'lng' => 101.6869],
        'notes' => 'Late arrival',
    ],
];

echo "Sample bulk data prepared (" . count($sampleBulkData) . " records)\n\n";

// ============================================================================
// RUNNING EXAMPLES (Pseudo-code)
// ============================================================================

echo "\n=== ADVANCED DEMO COMPLETE ===\n";
echo "These examples demonstrate:\n";
echo "✓ Multi-day aggregation and weekly summaries\n";
echo "✓ Late arrival detection with grace period logic\n";
echo "✓ Overtime calculation using work schedules\n";
echo "✓ Safe duplicate check-in handling\n";
echo "✓ Auto-completion for missing check-outs\n";
echo "✓ Bulk processing for imports/migrations\n";
echo "✓ Compliance reporting with attendance rates\n\n";

echo "To integrate into your application:\n";
echo "1. Inject all three domain services (AttendanceManager, WorkScheduleResolver, OvertimeCalculator)\n";
echo "2. Use repository interfaces for complex queries\n";
echo "3. Wrap operations in database transactions for consistency\n";
echo "4. Add logging and monitoring for production use\n\n";
