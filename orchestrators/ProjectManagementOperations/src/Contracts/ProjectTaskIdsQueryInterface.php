<?php

declare(strict_types=1);

namespace Nexus\ProjectManagementOperations\Contracts;

/**
 * Query task IDs linked to a project. Tenant-scoped; implementations must filter by tenantId.
 */
interface ProjectTaskIdsQueryInterface
{
    /**
     * @return list<string> Task IDs for the given project in the tenant.
     */
    public function getTaskIdsForProject(string $tenantId, string $projectId): array;
}
