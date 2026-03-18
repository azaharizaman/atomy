<?php

declare(strict_types=1);

namespace App\Services\Task;

use App\Models\Project;
use App\Models\Task;
use App\Services\Task\Exceptions\TaskNotFoundException;
use DomainException;

final readonly class TaskTenantLinkService
{
    public function setTaskProjectId(string $tenantId, string $taskId, ?string $projectId): void
    {
        $task = Task::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $taskId)
            ->first();

        if ($task === null) {
            throw new TaskNotFoundException($taskId, $tenantId);
        }

        if ($projectId !== null && $projectId !== '') {
            $exists = Project::query()
                ->where('tenant_id', $tenantId)
                ->where('id', $projectId)
                ->exists();

            if (! $exists) {
                throw new DomainException('Invalid project_id for current tenant.');
            }
        }

        $task->project_id = $projectId;
        $task->save();
    }
}

