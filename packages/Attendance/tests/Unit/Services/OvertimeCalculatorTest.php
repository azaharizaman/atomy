<?php

declare(strict_types=1);

namespace Nexus\Attendance\Tests\Unit\Services;

use Nexus\Attendance\Entities\AttendanceRecord;
use Nexus\Attendance\Entities\WorkSchedule;
use Nexus\Attendance\Services\OvertimeCalculator;
use Nexus\Attendance\ValueObjects\AttendanceId;
use Nexus\Attendance\ValueObjects\ScheduleId;
use Nexus\Attendance\ValueObjects\WorkHours;
use PHPUnit\Framework\TestCase;

final class OvertimeCalculatorTest extends TestCase
{
    private OvertimeCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new OvertimeCalculator();
    }

    public function test_calculate_overtime_returns_zero_when_record_not_complete(): void
    {
        $record = new AttendanceRecord(
            new AttendanceId('ATT-123'),
            'emp-123',
            new \DateTimeImmutable('2024-01-15'),
            new \DateTimeImmutable('2024-01-15 09:00:00')
        );

        $overtime = $this->calculator->calculateOvertime($record);

        $this->assertEquals(0.0, $overtime);
    }

    public function test_calculate_overtime_returns_overtime_hours_from_work_hours(): void
    {
        $workHours = new WorkHours(8.0, 2.0, 10.0);
        
        $record = new AttendanceRecord(
            new AttendanceId('ATT-123'),
            'emp-123',
            new \DateTimeImmutable('2024-01-15'),
            new \DateTimeImmutable('2024-01-15 09:00:00'),
            new \DateTimeImmutable('2024-01-15 19:00:00'),
            \Nexus\Attendance\Enums\AttendanceStatus::PRESENT,
            $workHours
        );

        $overtime = $this->calculator->calculateOvertime($record);

        $this->assertEquals(2.0, $overtime);
    }

    public function test_calculate_overtime_with_schedule_uses_expected_hours(): void
    {
        $workHours = new WorkHours(8.0, 2.0, 10.0);
        
        $record = new AttendanceRecord(
            new AttendanceId('ATT-123'),
            'emp-123',
            new \DateTimeImmutable('2024-01-15'),
            new \DateTimeImmutable('2024-01-15 09:00:00'),
            new \DateTimeImmutable('2024-01-15 19:00:00'),
            \Nexus\Attendance\Enums\AttendanceStatus::PRESENT,
            $workHours
        );

        $schedule = new WorkSchedule(
            new ScheduleId('SCH-123'),
            'emp-123',
            'Standard 9-5',
            new \DateTimeImmutable('09:00:00'),
            new \DateTimeImmutable('17:00:00'),
            new \DateTimeImmutable('2024-01-01'),
            null,
            null,
            8.0
        );

        $overtime = $this->calculator->calculateOvertimeWithSchedule($record, $schedule);

        $this->assertEquals(2.0, $overtime);
    }

    public function test_calculate_total_overtime_sums_multiple_records(): void
    {
        $record1 = new AttendanceRecord(
            new AttendanceId('ATT-123'),
            'emp-123',
            new \DateTimeImmutable('2024-01-15'),
            new \DateTimeImmutable('2024-01-15 09:00:00'),
            new \DateTimeImmutable('2024-01-15 19:00:00'),
            \Nexus\Attendance\Enums\AttendanceStatus::PRESENT,
            new WorkHours(8.0, 2.0, 10.0)
        );

        $record2 = new AttendanceRecord(
            new AttendanceId('ATT-124'),
            'emp-123',
            new \DateTimeImmutable('2024-01-16'),
            new \DateTimeImmutable('2024-01-16 09:00:00'),
            new \DateTimeImmutable('2024-01-16 18:30:00'),
            \Nexus\Attendance\Enums\AttendanceStatus::PRESENT,
            new WorkHours(8.0, 1.5, 9.5)
        );

        $totalOvertime = $this->calculator->calculateTotalOvertime([$record1, $record2]);

        $this->assertEquals(3.5, $totalOvertime);
    }

    public function test_is_overtime_exceeded_returns_true_when_overtime_exists(): void
    {
        $record = new AttendanceRecord(
            new AttendanceId('ATT-123'),
            'emp-123',
            new \DateTimeImmutable('2024-01-15'),
            new \DateTimeImmutable('2024-01-15 09:00:00'),
            new \DateTimeImmutable('2024-01-15 19:00:00'),
            \Nexus\Attendance\Enums\AttendanceStatus::PRESENT,
            new WorkHours(8.0, 2.0, 10.0)
        );

        $result = $this->calculator->isOvertimeExceeded($record);

        $this->assertTrue($result);
    }

    public function test_is_overtime_exceeded_returns_false_when_no_overtime(): void
    {
        $record = new AttendanceRecord(
            new AttendanceId('ATT-123'),
            'emp-123',
            new \DateTimeImmutable('2024-01-15'),
            new \DateTimeImmutable('2024-01-15 09:00:00'),
            new \DateTimeImmutable('2024-01-15 17:00:00'),
            \Nexus\Attendance\Enums\AttendanceStatus::PRESENT,
            new WorkHours(8.0, 0.0, 8.0)
        );

        $result = $this->calculator->isOvertimeExceeded($record);

        $this->assertFalse($result);
    }
}
