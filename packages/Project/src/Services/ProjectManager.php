<?php

declare(strict_types=1);

namespace Nexus\Project\Services;

use Nexus\Project\Contracts\IncompleteTaskCountInterface;
use Nexus\Project\Contracts\ProjectManagerInterface;
use Nexus\Project\Contracts\ProjectPersistInterface;
use Nexus\Project\Enums\ProjectStatus;
use Nexus\Project\Exceptions\ProjectCompletionException;
use Nexus\Project\ValueObjects\ProjectSummary;

/**
 * Project lifecycle. BUS-PRO-0042 (PM assigned) enforced in ProjectSummary.
 * BUS-PRO-0096 enforced via IncompleteTaskCountInterface when status = completed.
 */
final readonly class ProjectManager implements ProjectManagerInterface
{
    public function __construct(
        private ProjectPersistInterface $persist,
        private ?IncompleteTaskCountInterface $incompleteTaskCount = null,
    ) {
    }

    public function create(ProjectSummary $project): void
    {
        $this->persist->persist($project);
    }

    public function update(ProjectSummary $project): void
    {
        if ($project->status === ProjectStatus::Completed && $this->incompleteTaskCount !== null) {
            $count = $this->incompleteTaskCount->getIncompleteTaskCount($project->id);
            if ($count > 0) {
                throw ProjectCompletionException::hasIncompleteTasks($project->id, $count);
            }
        }
        $this->persist->persist($project);
    }
}
