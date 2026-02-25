<?php

declare(strict_types=1);

namespace Nexus\ProjectManagementOperations\Contracts;

use Nexus\ProjectManagementOperations\DTOs\ProjectDTO;

interface ProjectQueryInterface
{
    /**
     * Find project by ID
     */
    public function findById(string $id): ?ProjectDTO;

    /**
     * Get project's milestones
     * @return array<\Nexus\ProjectManagementOperations\DTOs\MilestoneDTO>
     */
    public function getMilestones(string $projectId): array;
}
