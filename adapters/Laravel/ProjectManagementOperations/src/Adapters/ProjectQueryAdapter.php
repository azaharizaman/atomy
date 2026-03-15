<?php

declare(strict_types=1);

namespace Nexus\Laravel\ProjectManagementOperations\Adapters;

use Nexus\ProjectManagementOperations\Contracts\ProjectQueryInterface;
use Nexus\ProjectManagementOperations\DTOs\MilestoneDTO;
use Nexus\ProjectManagementOperations\DTOs\ProjectDTO;
use Nexus\Project\Contracts\ProjectQueryInterface as L1ProjectQueryInterface;
use Nexus\Milestone\Contracts\MilestoneQueryInterface;

/**
 * Implements orchestrator ProjectQueryInterface using Nexus\Project and Nexus\Milestone.
 */
final readonly class ProjectQueryAdapter implements ProjectQueryInterface
{
    public function __construct(
        private L1ProjectQueryInterface $projectQuery,
        private MilestoneQueryInterface $milestoneQuery,
    ) {
    }

    public function findById(string $id): ?ProjectDTO
    {
        $project = $this->projectQuery->getById($id);
        if ($project === null) {
            return null;
        }
        return new ProjectDTO(
            id: $project->id,
            name: $project->name,
            startDate: $project->startDate,
            endDate: $project->endDate,
            status: $project->status->value
        );
    }

    /**
     * @return array<MilestoneDTO>
     */
    public function getMilestones(string $projectId): array
    {
        $milestones = $this->milestoneQuery->getByContext($projectId);
        $dtos = [];
        foreach ($milestones as $m) {
            $dtos[] = new MilestoneDTO(
                id: $m->id,
                projectId: $m->contextId,
                name: $m->title,
                dueDate: $m->dueDate ?? new \DateTimeImmutable(),
                completedAt: null,
                isBillable: $m->status->isBillable()
            );
        }
        return $dtos;
    }

    public function getProjectOwner(string $projectId): string
    {
        $project = $this->projectQuery->getById($projectId);
        return $project !== null ? $project->clientId : '';
    }
}
