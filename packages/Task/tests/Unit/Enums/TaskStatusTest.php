<?php

declare(strict_types=1);

namespace Nexus\Task\Tests\Unit\Enums;

use Nexus\Task\Enums\TaskStatus;
use PHPUnit\Framework\TestCase;

final class TaskStatusTest extends TestCase
{
    public function test_completed_is_terminal(): void
    {
        self::assertTrue(TaskStatus::Completed->isTerminal());
    }

    public function test_cancelled_is_terminal(): void
    {
        self::assertTrue(TaskStatus::Cancelled->isTerminal());
    }

    public function test_pending_is_not_terminal(): void
    {
        self::assertFalse(TaskStatus::Pending->isTerminal());
    }

    public function test_pending_can_transition_to_in_progress(): void
    {
        self::assertTrue(TaskStatus::Pending->canTransitionTo(TaskStatus::InProgress));
    }

    public function test_completed_cannot_transition(): void
    {
        self::assertFalse(TaskStatus::Completed->canTransitionTo(TaskStatus::Pending));
    }
}
