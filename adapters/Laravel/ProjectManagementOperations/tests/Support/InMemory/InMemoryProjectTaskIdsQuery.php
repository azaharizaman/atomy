<?php

declare(strict_types=1);

namespace Nexus\Laravel\ProjectManagementOperations\Tests\Support\InMemory;

use Nexus\Laravel\ProjectManagementOperations\Contracts\ProjectTaskIdsQueryInterface;

/**
 * In-memory app ProjectTaskIdsQueryInterface for integration tests.
 * Storage is keyed by tenantId then projectId for tenant isolation.
 */
final class InMemoryProjectTaskIdsQuery implements ProjectTaskIdsQueryInterface
{
    /** @var array<string, array<string, list<string>>> tenantId -> projectId -> task ids */
    private array $projectTaskIds = [];

    public function setTaskIdsForProject(string $tenantId, string $projectId, array $taskIds): void
    {
        $this->projectTaskIds[$tenantId][$projectId] = $taskIds;
    }

    /** @return list<string> */
    public function getTaskIdsForProject(string $tenantId, string $projectId): array
    {
        return ($this->projectTaskIds[$tenantId] ?? [])[$projectId] ?? [];
    }
}
