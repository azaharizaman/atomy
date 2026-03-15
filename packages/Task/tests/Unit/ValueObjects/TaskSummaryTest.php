<?php

declare(strict_types=1);

namespace Nexus\Task\Tests\Unit\ValueObjects;

use Nexus\Task\Enums\TaskPriority;
use Nexus\Task\Enums\TaskStatus;
use Nexus\Task\ValueObjects\TaskSummary;
use PHPUnit\Framework\TestCase;

final class TaskSummaryTest extends TestCase
{
    public function test_is_complete_when_status_completed(): void
    {
        $task = new TaskSummary(
            '1',
            'Done',
            'Desc',
            TaskStatus::Completed,
            TaskPriority::Low,
            null,
            [],
            [],
            new \DateTimeImmutable()
        );
        self::assertTrue($task->isComplete());
    }

    public function test_is_not_complete_when_pending(): void
    {
        $task = new TaskSummary(
            '1',
            'Todo',
            'Desc',
            TaskStatus::Pending,
            TaskPriority::Medium,
            null,
            [],
            []
        );
        self::assertFalse($task->isComplete());
    }

    public function test_has_predecessors(): void
    {
        $task = new TaskSummary(
            '1',
            'Task',
            'Desc',
            TaskStatus::Pending,
            TaskPriority::Medium,
            null,
            [],
            ['p1', 'p2']
        );
        self::assertTrue($task->hasPredecessors());
    }

    public function test_empty_title_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Task title cannot be empty');
        new TaskSummary(
            '1',
            '',
            'Desc',
            TaskStatus::Pending,
            TaskPriority::Medium,
            null,
            [],
            []
        );
    }
}
