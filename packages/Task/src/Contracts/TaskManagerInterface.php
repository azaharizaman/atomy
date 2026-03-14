<?php

declare(strict_types=1);

namespace Nexus\Task\Contracts;

use Nexus\Task\ValueObjects\TaskSummary;

/**
 * Task lifecycle and business operations (FUN-PRO-0242, FUN-PRO-0565).
 * Caller provides parent context (e.g. project ID) when needed.
 */
interface TaskManagerInterface
{
    /**
     * Create a new task. Validates dependency graph does not introduce a cycle (BUS-PRO-0090).
     *
     * @param TaskSummary $task Task data; id must be unique.
     * @param array<string, list<string>> $existingGraph Existing taskId => predecessorIds for cycle check.
     * @throws \Nexus\Task\Exceptions\CircularDependencyException If adding this task would create a cycle.
     */
    public function create(TaskSummary $task, array $existingGraph = []): void;

    /**
     * Update an existing task. Validates dependency graph remains acyclic if predecessorIds change.
     *
     * @param TaskSummary $task Updated task data.
     * @param array<string, list<string>> $fullGraph Full taskId => predecessorIds including this task.
     * @throws \Nexus\Task\Exceptions\CircularDependencyException If update would create a cycle.
     */
    public function update(TaskSummary $task, array $fullGraph = []): void;

    /**
     * Validate that the given dependency graph has no cycles (BUS-PRO-0090).
     *
     * @param array<string, list<string>> $taskIdToPredecessorIds Map of task id => list of predecessor task ids.
     * @throws \Nexus\Task\Exceptions\CircularDependencyException If a cycle is detected.
     */
    public function validateDependencies(array $taskIdToPredecessorIds): void;
}
