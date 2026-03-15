<?php

declare(strict_types=1);

namespace Nexus\Laravel\ProjectManagementOperations\Adapters;

use Nexus\ProjectManagementOperations\Contracts\SchedulerQueryInterface;
use Nexus\Project\Contracts\ProjectQueryInterface as L1ProjectQueryInterface;

/**
 * Implements orchestrator SchedulerQueryInterface using project start/end from Nexus\Project.
 */
final readonly class SchedulerQueryAdapter implements SchedulerQueryInterface
{
    public function __construct(
        private L1ProjectQueryInterface $projectQuery,
    ) {
    }

    public function getScheduledDates(string $projectId): ?array
    {
        $project = $this->projectQuery->getById($projectId);
        if ($project === null) {
            return null;
        }
        return [
            'start' => $project->startDate,
            'end' => $project->endDate,
        ];
    }
}
