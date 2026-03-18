<?php

declare(strict_types=1);

namespace App\Services\ProjectManagementOperations;

use App\Models\Task as TaskModel;
use Nexus\ProjectManagementOperations\Contracts\ProjectTaskIdsQueryInterface;

final readonly class AtomyProjectTaskIdsQuery implements ProjectTaskIdsQueryInterface
{
    /**
     * @return list<string>
     */
    public function getTaskIdsForProject(string $tenantId, string $projectId): array
    {
        return TaskModel::query()
            ->where('tenant_id', $tenantId)
            ->where('project_id', $projectId)
            ->orderBy('created_at')
            ->pluck('id')
            ->values()
            ->all();
    }
}
