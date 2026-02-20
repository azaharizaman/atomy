<?php

declare(strict_types=1);

namespace Nexus\Attendance\Tests\Unit\ValueObjects;

use Nexus\Attendance\ValueObjects\ScheduleId;
use PHPUnit\Framework\TestCase;

final class ScheduleIdTest extends TestCase
{
    public function test_creates_schedule_id_with_valid_value(): void
    {
        $id = new ScheduleId('SCH-123456');
        
        $this->assertEquals('SCH-123456', $id->value);
        $this->assertEquals('SCH-123456', $id->toString());
    }

    public function test_throws_exception_when_value_is_empty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Schedule ID cannot be empty');
        
        new ScheduleId('');
    }

    public function test_equals_returns_true_for_same_value(): void
    {
        $id1 = new ScheduleId('SCH-123');
        $id2 = new ScheduleId('SCH-123');
        
        $this->assertTrue($id1->equals($id2));
    }

    public function test_equals_returns_false_for_different_values(): void
    {
        $id1 = new ScheduleId('SCH-123');
        $id2 = new ScheduleId('SCH-456');
        
        $this->assertFalse($id1->equals($id2));
    }
}
