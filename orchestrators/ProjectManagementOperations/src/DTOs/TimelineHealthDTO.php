<?php

declare(strict_types=1);

namespace Nexus\ProjectManagementOperations\DTOs;

final readonly class TimelineHealthDTO
{
    public function __construct(
        public string $projectId,
        public int $totalMilestones,
        public int $completedMilestones,
        public int $delayedMilestones,
        public float $completionPercentage,
        public array $driftDetails
    ) {
    }
}
