<?php

declare(strict_types=1);

namespace Nexus\Workflow\Contracts;

/**
 * Persistence contract for workflow tasks.
 */
interface TaskRepositoryInterface
{
    /**
     * Find a task by ID.
     *
     * @throws \Nexus\Workflow\Exceptions\TaskNotFoundException
     */
    public function findById(string $id): TaskInterface;

    /**
     * Find tasks for a specific workflow.
     *
     * @return TaskInterface[]
     */
    public function findByWorkflow(string $workflowId): array;

    /**
     * Find pending tasks assigned to a user.
     *
     * @return TaskInterface[]
     */
    public function findPendingForUser(string $userId): array;

    /**
     * Find pending tasks assigned to a role.
     *
     * @return TaskInterface[]
     */
    public function findPendingForRole(string $role): array;

    /**
     * Find overdue tasks.
     *
     * @return TaskInterface[]
     */
    public function findOverdue(): array;

    /**
     * Save task.
     */
    public function save(TaskInterface $task): void;

    /**
     * Delete task.
     */
    public function delete(string $id): void;
}
