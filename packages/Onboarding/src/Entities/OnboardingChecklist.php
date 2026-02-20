<?php

declare(strict_types=1);

namespace Nexus\Onboarding\Entities;

use Nexus\Onboarding\ValueObjects\ChecklistId;

final readonly class OnboardingChecklist
{
    public function __construct(
        public ChecklistId $id,
        public string $employeeId,
        public \DateTimeImmutable $startDate,
        public ?\DateTimeImmutable $completedAt = null,
        public float $progressPercentage = 0.0,
    ) {}
}
