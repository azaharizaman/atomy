<?php

declare(strict_types=1);

namespace App\Services\Project;

use App\Models\ProjectAcl;

final class ProjectAclService
{
    public function userCanAccessProject(string $tenantId, string $userId, string $projectId): bool
    {
        return ProjectAcl::query()
            ->where('project_id', $projectId)
            ->where('user_id', $userId)
            ->where('tenant_id', $tenantId)
            ->exists();
    }
}
