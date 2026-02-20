<?php

declare(strict_types=1);

namespace Nexus\Attendance\Tests\Unit\Entities;

use Nexus\Attendance\Entities\WorkSchedule;
use Nexus\Attendance\ValueObjects\ScheduleId;
use PHPUnit\Framework\TestCase;

final class WorkScheduleTest extends TestCase
{
    public function test_creates_work_schedule_with_required_fields(): void
    {
        $id = new ScheduleId('SCH-123');
        $startTime = new \DateTimeImmutable('09:00:00');
        $endTime = new \DateTimeImmutable('17:00:00');
        $effectiveFrom = new \DateTimeImmutable('2024-01-01');
        
        $schedule = new WorkSchedule(
            $id,
            'emp-123',
            'Standard 9-5',
            $startTime,
            $endTime,
            $effectiveFrom
        );
        
        $this->assertEquals($id, $schedule->getId());
        $this->assertEquals('emp-123', $schedule->getEmployeeId());
        $this->assertEquals('Standard 9-5', $schedule->getScheduleName());
        $this->assertEquals(8.0, $schedule->getExpectedHours());
    }

    public function test_is_effective_on_returns_true_for_valid_date(): void
    {
        $id = new ScheduleId('SCH-123');
        $startTime = new \DateTimeImmutable('09:00:00');
        $endTime = new \DateTimeImmutable('17:00:00');
        $effectiveFrom = new \DateTimeImmutable('2024-01-01');
        $effectiveTo = new \DateTimeImmutable('2024-12-31');
        
        $schedule = new WorkSchedule(
            $id,
            'emp-123',
            'Standard 9-5',
            $startTime,
            $endTime,
            $effectiveFrom,
            $effectiveTo
        );
        
        $testDate = new \DateTimeImmutable('2024-06-15');
        $this->assertTrue($schedule->isEffectiveOn($testDate));
    }

    public function test_is_effective_on_returns_false_for_date_before_effective_from(): void
    {
        $id = new ScheduleId('SCH-123');
        $startTime = new \DateTimeImmutable('09:00:00');
        $endTime = new \DateTimeImmutable('17:00:00');
        $effectiveFrom = new \DateTimeImmutable('2024-01-01');
        
        $schedule = new WorkSchedule($id, 'emp-123', 'Standard 9-5', $startTime, $endTime, $effectiveFrom);
        
        $testDate = new \DateTimeImmutable('2023-12-31');
        $this->assertFalse($schedule->isEffectiveOn($testDate));
    }

    public function test_is_effective_on_returns_false_for_date_after_effective_to(): void
    {
        $id = new ScheduleId('SCH-123');
        $startTime = new \DateTimeImmutable('09:00:00');
        $endTime = new \DateTimeImmutable('17:00:00');
        $effectiveFrom = new \DateTimeImmutable('2024-01-01');
        $effectiveTo = new \DateTimeImmutable('2024-12-31');
        
        $schedule = new WorkSchedule($id, 'emp-123', 'Standard 9-5', $startTime, $endTime, $effectiveFrom, $effectiveTo);
        
        $testDate = new \DateTimeImmutable('2025-01-01');
        $this->assertFalse($schedule->isEffectiveOn($testDate));
    }

    public function test_is_effective_on_checks_day_of_week_when_specified(): void
    {
        $id = new ScheduleId('SCH-123');
        $startTime = new \DateTimeImmutable('09:00:00');
        $endTime = new \DateTimeImmutable('17:00:00');
        $effectiveFrom = new \DateTimeImmutable('2024-01-01');
        $dayOfWeek = 1; // Monday
        
        $schedule = new WorkSchedule(
            $id,
            'emp-123',
            'Monday Schedule',
            $startTime,
            $endTime,
            $effectiveFrom,
            null,
            $dayOfWeek
        );
        
        $monday = new \DateTimeImmutable('2024-01-15'); // Monday
        $tuesday = new \DateTimeImmutable('2024-01-16'); // Tuesday
        
        $this->assertTrue($schedule->isEffectiveOn($monday));
        $this->assertFalse($schedule->isEffectiveOn($tuesday));
    }

    public function test_is_late_check_in_returns_true_when_after_grace_period(): void
    {
        $id = new ScheduleId('SCH-123');
        $startTime = new \DateTimeImmutable('2024-01-15 09:00:00');
        $endTime = new \DateTimeImmutable('2024-01-15 17:00:00');
        $effectiveFrom = new \DateTimeImmutable('2024-01-01');
        
        $schedule = new WorkSchedule($id, 'emp-123', 'Standard 9-5', $startTime, $endTime, $effectiveFrom);
        
        $checkInTime = new \DateTimeImmutable('2024-01-15 09:20:00'); // 20 minutes late
        
        $this->assertTrue($schedule->isLateCheckIn($checkInTime, 15));
    }

    public function test_is_late_check_in_returns_false_when_within_grace_period(): void
    {
        $id = new ScheduleId('SCH-123');
        $startTime = new \DateTimeImmutable('2024-01-15 09:00:00');
        $endTime = new \DateTimeImmutable('2024-01-15 17:00:00');
        $effectiveFrom = new \DateTimeImmutable('2024-01-01');
        
        $schedule = new WorkSchedule($id, 'emp-123', 'Standard 9-5', $startTime, $endTime, $effectiveFrom);
        
        $checkInTime = new \DateTimeImmutable('2024-01-15 09:10:00'); // 10 minutes late
        
        $this->assertFalse($schedule->isLateCheckIn($checkInTime, 15));
    }

    public function test_is_early_check_out_returns_true_when_before_grace_period(): void
    {
        $id = new ScheduleId('SCH-123');
        $startTime = new \DateTimeImmutable('2024-01-15 09:00:00');
        $endTime = new \DateTimeImmutable('2024-01-15 17:00:00');
        $effectiveFrom = new \DateTimeImmutable('2024-01-01');
        
        $schedule = new WorkSchedule($id, 'emp-123', 'Standard 9-5', $startTime, $endTime, $effectiveFrom);
        
        $checkOutTime = new \DateTimeImmutable('2024-01-15 16:30:00'); // 30 minutes early
        
        $this->assertTrue($schedule->isEarlyCheckOut($checkOutTime, 15));
    }
}
