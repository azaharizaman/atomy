<?php

declare(strict_types=1);

namespace Nexus\Project\ValueObjects;

use DateTimeImmutable;
use Nexus\Project\Enums\ProjectStatus;

/**
 * Immutable project summary (FUN-PRO-0564). BUS-PRO-0042: projectManagerId must be set.
 */
final readonly class ProjectSummary
{
    public function __construct(
        public string $id,
        public string $name,
        public string $clientId,
        public DateTimeImmutable $startDate,
        public DateTimeImmutable $endDate,
        public string $projectManagerId,
        public ProjectStatus $status,
        /** Budget type e.g. fixed_price, time_and_materials */
        public string $budgetType = 'time_and_materials',
        /** Completion percentage 0-100; set by orchestrator from task completion */
        public float $completionPercentage = 0.0,
    ) {
        if ($name === '') {
            throw new \InvalidArgumentException('Project name cannot be empty.');
        }
        if ($projectManagerId === '') {
            throw new \InvalidArgumentException('Project manager must be assigned (BUS-PRO-0042).');
        }
        if ($completionPercentage < 0 || $completionPercentage > 100) {
            throw new \InvalidArgumentException('Completion percentage must be between 0 and 100.');
        }
    }

    public function isCompleted(): bool
    {
        return $this->status === ProjectStatus::Completed;
    }
}
