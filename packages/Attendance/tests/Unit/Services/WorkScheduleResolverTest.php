<?php

declare(strict_types=1);

namespace Nexus\Attendance\Tests\Unit\Services;

use Nexus\Attendance\Contracts\WorkScheduleQueryInterface;
use Nexus\Attendance\Entities\WorkSchedule;
use Nexus\Attendance\Exceptions\WorkScheduleNotFoundException;
use Nexus\Attendance\Services\WorkScheduleResolver;
use Nexus\Attendance\ValueObjects\ScheduleId;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class WorkScheduleResolverTest extends TestCase
{
    private WorkScheduleResolver $resolver;
    private MockObject $scheduleQuery;

    protected function setUp(): void
    {
        $this->scheduleQuery = $this->createMock(WorkScheduleQueryInterface::class);
        $this->resolver = new WorkScheduleResolver($this->scheduleQuery);
    }

    public function test_resolve_schedule_returns_schedule_when_found(): void
    {
        $employeeId = 'emp-123';
        $date = new \DateTimeImmutable('2024-01-15');
        
        $expectedSchedule = new WorkSchedule(
            new ScheduleId('SCH-123'),
            $employeeId,
            'Standard 9-5',
            new \DateTimeImmutable('09:00:00'),
            new \DateTimeImmutable('17:00:00'),
            new \DateTimeImmutable('2024-01-01')
        );

        $this->scheduleQuery
            ->expects($this->once())
            ->method('findEffectiveSchedule')
            ->with($employeeId, $date)
            ->willReturn($expectedSchedule);

        $result = $this->resolver->resolveSchedule($employeeId, $date);

        $this->assertEquals($expectedSchedule, $result);
    }

    public function test_resolve_schedule_throws_exception_when_not_found(): void
    {
        $employeeId = 'emp-123';
        $date = new \DateTimeImmutable('2024-01-15');

        $this->scheduleQuery
            ->expects($this->once())
            ->method('findEffectiveSchedule')
            ->willReturn(null);

        $this->expectException(WorkScheduleNotFoundException::class);

        $this->resolver->resolveSchedule($employeeId, $date);
    }

    public function test_try_resolve_schedule_returns_null_when_not_found(): void
    {
        $employeeId = 'emp-123';
        $date = new \DateTimeImmutable('2024-01-15');

        $this->scheduleQuery
            ->expects($this->once())
            ->method('findEffectiveSchedule')
            ->willReturn(null);

        $result = $this->resolver->tryResolveSchedule($employeeId, $date);

        $this->assertNull($result);
    }

    public function test_has_schedule_returns_true_when_schedule_exists(): void
    {
        $employeeId = 'emp-123';
        $date = new \DateTimeImmutable('2024-01-15');
        
        $schedule = new WorkSchedule(
            new ScheduleId('SCH-123'),
            $employeeId,
            'Standard 9-5',
            new \DateTimeImmutable('09:00:00'),
            new \DateTimeImmutable('17:00:00'),
            new \DateTimeImmutable('2024-01-01')
        );

        $this->scheduleQuery
            ->expects($this->once())
            ->method('findEffectiveSchedule')
            ->willReturn($schedule);

        $result = $this->resolver->hasSchedule($employeeId, $date);

        $this->assertTrue($result);
    }

    public function test_has_schedule_returns_false_when_schedule_not_found(): void
    {
        $employeeId = 'emp-123';
        $date = new \DateTimeImmutable('2024-01-15');

        $this->scheduleQuery
            ->expects($this->once())
            ->method('findEffectiveSchedule')
            ->willReturn(null);

        $result = $this->resolver->hasSchedule($employeeId, $date);

        $this->assertFalse($result);
    }
}
