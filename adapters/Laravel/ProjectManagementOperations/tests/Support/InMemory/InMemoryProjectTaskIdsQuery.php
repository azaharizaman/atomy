<?php

declare(strict_types=1);

namespace Nexus\Laravel\ProjectManagementOperations\Tests\Support\InMemory;

use Nexus\Laravel\ProjectManagementOperations\Contracts\ProjectTaskIdsQueryInterface;

/**
 * In-memory app ProjectTaskIdsQueryInterface for integration tests.
 */
final class InMemoryProjectTaskIdsQuery implements ProjectTaskIdsQueryInterface
{
    /** @var array<string, list<string>> projectId -> task ids */
    private array $projectTaskIds = [];

    public function setTaskIdsForProject(string $projectId, array $taskIds): void
    {
        $this->projectTaskIds[$projectId] = $taskIds;
    }

    /** @return list<string> */
    public function getTaskIdsForProject(string $projectId): array
    {
        return $this->projectTaskIds[$projectId] ?? [];
    }
}
