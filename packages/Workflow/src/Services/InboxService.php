<?php

declare(strict_types=1);

namespace Nexus\Workflow\Services;

use Nexus\Workflow\Contracts\{
    TaskInterface,
    TaskRepositoryInterface,
    DelegationRepositoryInterface
};

/**
 * Inbox Service - Task inbox queries.
 *
 * Public API for retrieving user tasks.
 */
final readonly class InboxService
{
    public function __construct(
        private TaskRepositoryInterface $taskRepository,
        private DelegationRepositoryInterface $delegationRepository
    ) {}

    /**
     * Get pending tasks for a user.
     *
     * Includes tasks assigned directly and via delegation.
     *
     * @return TaskInterface[]
     */
    public function forUser(string $userId): array
    {
        $tasks = $this->taskRepository->findPendingForUser($userId);
        
        // Also include tasks from delegation chain
        $delegations = $this->delegationRepository->findActiveForUser($userId);
        foreach ($delegations as $delegation) {
            $delegatedTasks = $this->taskRepository->findPendingForUser($delegation->getDelegatorId());
            $tasks = array_merge($tasks, $delegatedTasks);
        }
        
        return $tasks;
    }

    /**
     * Get pending tasks assigned to a role.
     *
     * @return TaskInterface[]
     */
    public function forRole(string $role): array
    {
        return $this->taskRepository->findPendingForRole($role);
    }

    /**
     * Get overdue tasks.
     *
     * @return TaskInterface[]
     */
    public function overdue(): array
    {
        return $this->taskRepository->findOverdue();
    }

    /**
     * Filter tasks by criteria.
     *
     * @param array<string, mixed> $criteria
     * @return TaskInterface[]
     */
    public function filter(array $criteria): array
    {
        // Apply filters based on criteria
        // Implementation depends on repository capabilities
        throw new \RuntimeException('Implementation required in repository layer');
    }
}
