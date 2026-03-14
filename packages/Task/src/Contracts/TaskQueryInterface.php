<?php

declare(strict_types=1);

namespace Nexus\Task\Contracts;

use Nexus\Task\ValueObjects\TaskSummary;

/**
 * Read-only task query contract (FUN-PRO-0260, FUN-PRO-0567).
 */
interface TaskQueryInterface
{
    /**
     * @return TaskSummary|null Task data or null if not found.
     */
    public function getById(string $taskId): ?TaskSummary;

    /**
     * Predecessor task IDs for the given task.
     *
     * @return list<string>
     */
    public function getPredecessorIds(string $taskId): array;

    /**
     * Tasks assigned to the given user (for "My Tasks" view).
     *
     * @return list<TaskSummary>
     */
    public function getByAssignee(string $assigneeId): array;
}
