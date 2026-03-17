<?php

declare(strict_types=1);

namespace App\Services\ProjectManagementOperations;

use Nexus\ProjectManagementOperations\Contracts\TimelineDriftServiceInterface;
use Nexus\ProjectManagementOperations\DTOs\TimelineHealthDTO;

final readonly class AtomyTimelineDriftService implements TimelineDriftServiceInterface
{
    public function calculate(string $tenantId, string $projectId, ?\DateTimeImmutable $now = null): TimelineHealthDTO
    {
        return new TimelineHealthDTO(
            projectId: $projectId,
            totalMilestones: 0,
            completedMilestones: 0,
            delayedMilestones: 0,
            completionPercentage: 0.0,
            driftDetails: []
        );
    }
}
