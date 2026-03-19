<?php

declare(strict_types=1);

namespace App\Services\Project;

use App\Models\ProjectAcl;

final readonly class ProjectAclService
{
    /**
     * Access levels (highest -> lowest):
     * owner > manager > contributor > viewer (and client_stakeholder treated as viewer-level).
     */
    private const ROLE_RANK = [
        'owner' => 4,
        'manager' => 3,
        'contributor' => 2,
        'viewer' => 1,
        'client_stakeholder' => 1,
    ];

    public function userCanViewProject(string $tenantId, string $userId, string $projectId): bool
    {
        return $this->userHasAtLeastRole($tenantId, $userId, $projectId, 'viewer');
    }

    public function userCanEditProject(string $tenantId, string $userId, string $projectId): bool
    {
        return $this->userHasAtLeastRole($tenantId, $userId, $projectId, 'contributor');
    }

    public function userCanManageProjectAcl(string $tenantId, string $userId, string $projectId): bool
    {
        return $this->userHasAtLeastRole($tenantId, $userId, $projectId, 'manager');
    }

    public function userHasAtLeastRole(string $tenantId, string $userId, string $projectId, string $requiredRole): bool
    {
        $row = ProjectAcl::query()
            ->where('project_id', $projectId)
            ->where('user_id', $userId)
            ->where('tenant_id', $tenantId)
            ->first(['role']);

        if ($row === null) {
            return false;
        }

        $requiredRank = self::ROLE_RANK[$requiredRole] ?? null;
        if ($requiredRank === null) {
            return false;
        }

        $actualRank = self::ROLE_RANK[(string) $row->role] ?? null;
        if ($actualRank === null) {
            return false;
        }

        return $actualRank >= $requiredRank;
    }
}
