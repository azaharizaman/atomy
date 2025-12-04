<?php

declare(strict_types=1);

namespace Nexus\AttendanceManagement\Services;

use Nexus\AttendanceManagement\Contracts\AttendanceRecordInterface;
use Nexus\AttendanceManagement\Contracts\WorkScheduleInterface;

/**
 * Calculates overtime hours based on work schedule and actual hours
 */
final readonly class OvertimeCalculator
{
    public function __construct(
        private float $overtimeThreshold = 8.0
    ) {}

    /**
     * Calculate overtime hours for an attendance record
     */
    public function calculateOvertime(AttendanceRecordInterface $attendance): float
    {
        if (!$attendance->isComplete() || $attendance->getWorkHours() === null) {
            return 0.0;
        }

        return $attendance->getWorkHours()->overtimeHours;
    }

    /**
     * Calculate overtime hours with schedule context
     */
    public function calculateOvertimeWithSchedule(
        AttendanceRecordInterface $attendance,
        WorkScheduleInterface $schedule
    ): float {
        if (!$attendance->isComplete() || $attendance->getWorkHours() === null) {
            return 0.0;
        }

        $actualHours = $attendance->getWorkHours()->getTotalHours();
        $expectedHours = $schedule->getExpectedHours();
        
        return max(0.0, $actualHours - $expectedHours);
    }

    /**
     * Calculate total overtime hours for multiple attendance records
     * 
     * @param array<AttendanceRecordInterface> $attendanceRecords
     */
    public function calculateTotalOvertime(array $attendanceRecords): float
    {
        $totalOvertime = 0.0;
        
        foreach ($attendanceRecords as $attendance) {
            $totalOvertime += $this->calculateOvertime($attendance);
        }
        
        return $totalOvertime;
    }

    /**
     * Check if overtime threshold exceeded
     */
    public function isOvertimeExceeded(AttendanceRecordInterface $attendance): bool
    {
        return $this->calculateOvertime($attendance) > 0;
    }
}
