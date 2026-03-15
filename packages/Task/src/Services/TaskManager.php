<?php

declare(strict_types=1);

namespace Nexus\Task\Services;

use Nexus\Task\Contracts\DependencyGraphInterface;
use Nexus\Task\Contracts\TaskManagerInterface;
use Nexus\Task\Contracts\TaskPersistInterface;
use Nexus\Task\Exceptions\CircularDependencyException;
use Nexus\Task\ValueObjects\TaskSummary;

/**
 * Task lifecycle: create, update, validate dependencies (BUS-PRO-0090).
 * Persistence and query are delegated to contracts.
 */
final readonly class TaskManager implements TaskManagerInterface
{
    public function __construct(
        private TaskPersistInterface $persist,
        private DependencyGraphInterface $dependencyGraph,
    ) {
    }

    public function create(TaskSummary $task, array $existingGraph = []): void
    {
        $graph = $existingGraph;
        $graph[$task->id] = $task->predecessorIds;
        $this->validateDependencies($graph);
        $this->persist->persist($task);
    }

    public function update(TaskSummary $task, array $fullGraph = []): void
    {
        if ($fullGraph !== []) {
            $this->validateDependencies($fullGraph);
        }
        $this->persist->persist($task);
    }

    public function validateDependencies(array $taskIdToPredecessorIds): void
    {
        if ($this->dependencyGraph->hasCycle($taskIdToPredecessorIds)) {
            throw CircularDependencyException::new();
        }
    }
}
