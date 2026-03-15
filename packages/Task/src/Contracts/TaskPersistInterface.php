<?php

declare(strict_types=1);

namespace Nexus\Task\Contracts;

use Nexus\Task\ValueObjects\TaskSummary;

/**
 * Task persistence (write side). CQRS split.
 */
interface TaskPersistInterface
{
    public function persist(TaskSummary $task): void;

    public function delete(string $taskId): void;
}
