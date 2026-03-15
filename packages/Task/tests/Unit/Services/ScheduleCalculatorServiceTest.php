<?php

declare(strict_types=1);

namespace Nexus\Task\Tests\Unit\Services;

use DateTimeImmutable;
use Nexus\Task\Enums\TaskPriority;
use Nexus\Task\Enums\TaskStatus;
use Nexus\Task\Services\ScheduleCalculatorService;
use Nexus\Task\ValueObjects\TaskSummary;
use PHPUnit\Framework\TestCase;

final class ScheduleCalculatorServiceTest extends TestCase
{
    private ScheduleCalculatorService $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new ScheduleCalculatorService();
    }

    public function test_empty_tasks_returns_empty_schedule(): void
    {
        $result = $this->calculator->computeSchedule([], [], null);
        self::assertSame([], $result);
    }

    public function test_single_task_no_dependencies(): void
    {
        $start = new DateTimeImmutable('2025-01-01 00:00:00');
        $task = new TaskSummary(
            't1',
            'Single',
            'Desc',
            TaskStatus::Pending,
            TaskPriority::Medium,
            null,
            [],
            []
        );
        $result = $this->calculator->computeSchedule([$task], [], $start);
        self::assertArrayHasKey('t1', $result);
        self::assertEquals($start, $result['t1']['earlyStart']);
        self::assertEquals($start, $result['t1']['lateStart']);
    }

    public function test_two_tasks_linear_dependency(): void
    {
        $start = new DateTimeImmutable('2025-01-01 00:00:00');
        $t1 = new TaskSummary('t1', 'First', '', TaskStatus::Pending, TaskPriority::Medium, null, [], []);
        $t2 = new TaskSummary('t2', 'Second', '', TaskStatus::Pending, TaskPriority::Medium, null, [], ['t1']);
        $result = $this->calculator->computeSchedule([$t1, $t2], ['t1' => [], 't2' => ['t1']], $start);
        self::assertArrayHasKey('t1', $result);
        self::assertArrayHasKey('t2', $result);
        self::assertGreaterThanOrEqual(
            $result['t1']['earlyFinish']->getTimestamp(),
            $result['t2']['earlyStart']->getTimestamp()
        );
    }
}
