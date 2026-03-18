<?php

declare(strict_types=1);

namespace App\Services\Task;

use Nexus\Task\Contracts\TaskManagerInterface;
use Nexus\Task\Contracts\TaskQueryInterface as TaskDomainQueryInterface;
use Nexus\Task\ValueObjects\TaskSummary;

final readonly class TaskService
{
    public function __construct(
        private TaskManagerInterface $manager,
        private TaskDomainQueryInterface $query,
    ) {
    }

    public function create(TaskSummary $task, array $existingGraph = []): void
    {
        $this->manager->create($task, $existingGraph);
    }

    public function update(TaskSummary $task, array $fullGraph = []): void
    {
        $this->manager->update($task, $fullGraph);
    }

    /**
     * @param array<string, list<string>> $taskIdToPredecessorIds
     */
    public function validateDependencies(array $taskIdToPredecessorIds): void
    {
        $this->manager->validateDependencies($taskIdToPredecessorIds);
    }

    public function findById(string $taskId): ?TaskSummary
    {
        return $this->query->getById($taskId);
    }
}

