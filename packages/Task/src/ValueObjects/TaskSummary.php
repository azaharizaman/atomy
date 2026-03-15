<?php

declare(strict_types=1);

namespace Nexus\Task\ValueObjects;

use DateTimeImmutable;
use Nexus\Task\Enums\TaskPriority;
use Nexus\Task\Enums\TaskStatus;

/**
 * Immutable value object for task data (FUN-PRO-0242, FUN-PRO-0565).
 * Parent context (e.g. project ID) is supplied by caller, not stored here.
 */
final readonly class TaskSummary
{
    public function __construct(
        public string $id,
        public string $title,
        public string $description,
        public TaskStatus $status,
        public TaskPriority $priority,
        public ?DateTimeImmutable $dueDate,
        /** @var list<string> Assignee identifiers */
        public array $assigneeIds,
        /** @var list<string> Predecessor task IDs for dependency graph (BUS-PRO-0090) */
        public array $predecessorIds,
        public ?DateTimeImmutable $completedAt = null,
    ) {
        if ($title === '') {
            throw new \InvalidArgumentException('Task title cannot be empty.');
        }
    }

    public function isComplete(): bool
    {
        return $this->status->isTerminal() && $this->status === TaskStatus::Completed;
    }

    /** Check if this task has any predecessor dependency */
    public function hasPredecessors(): bool
    {
        return $this->predecessorIds !== [];
    }
}
