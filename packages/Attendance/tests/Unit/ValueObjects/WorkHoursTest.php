<?php

declare(strict_types=1);

namespace Nexus\Attendance\Tests\Unit\ValueObjects;

use Nexus\Attendance\ValueObjects\WorkHours;
use PHPUnit\Framework\TestCase;

final class WorkHoursTest extends TestCase
{
    public function test_creates_work_hours_with_valid_values(): void
    {
        $workHours = new WorkHours(8.0, 2.0, 10.0);
        
        $this->assertEquals(8.0, $workHours->regularHours);
        $this->assertEquals(2.0, $workHours->overtimeHours);
        $this->assertEquals(10.0, $workHours->totalHours);
    }

    public function test_throws_exception_when_regular_hours_is_negative(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Regular hours cannot be negative');
        
        new WorkHours(-1.0, 0.0, 0.0);
    }

    public function test_throws_exception_when_overtime_hours_is_negative(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Overtime hours cannot be negative');
        
        new WorkHours(8.0, -1.0, 0.0);
    }

    public function test_from_duration_calculates_hours_correctly_without_overtime(): void
    {
        $checkIn = new \DateTimeImmutable('2024-01-15 09:00:00');
        $checkOut = new \DateTimeImmutable('2024-01-15 17:00:00');
        
        $workHours = WorkHours::fromDuration($checkIn, $checkOut);
        
        $this->assertEquals(8.0, $workHours->regularHours);
        $this->assertEquals(0.0, $workHours->overtimeHours);
        $this->assertEquals(8.0, $workHours->totalHours);
    }

    public function test_from_duration_calculates_hours_correctly_with_overtime(): void
    {
        $checkIn = new \DateTimeImmutable('2024-01-15 09:00:00');
        $checkOut = new \DateTimeImmutable('2024-01-15 19:00:00'); // 10 hours
        
        $workHours = WorkHours::fromDuration($checkIn, $checkOut);
        
        $this->assertEquals(8.0, $workHours->regularHours);
        $this->assertEquals(2.0, $workHours->overtimeHours);
        $this->assertEquals(10.0, $workHours->getTotalHours());
    }

    public function test_from_duration_with_custom_standard_hours(): void
    {
        $checkIn = new \DateTimeImmutable('2024-01-15 09:00:00');
        $checkOut = new \DateTimeImmutable('2024-01-15 16:00:00'); // 7 hours
        
        $workHours = WorkHours::fromDuration($checkIn, $checkOut, 6.0);
        
        $this->assertEquals(6.0, $workHours->regularHours);
        $this->assertEquals(1.0, $workHours->overtimeHours);
    }

    public function test_get_total_hours_returns_sum_of_regular_and_overtime(): void
    {
        $workHours = new WorkHours(8.0, 2.5, 10.5);
        
        $this->assertEquals(10.5, $workHours->getTotalHours());
    }
}
