<?php

declare(strict_types=1);

namespace Nexus\Onboarding\Entities;

use Nexus\Onboarding\ValueObjects\TaskStatus;

final readonly class OnboardingTask
{
    public function __construct(
        public string $id,
        public string $checklistId,
        public string $title,
        public string $description,
        public TaskStatus $status,
        public ?string $assignedTo = null,
        public ?\DateTimeImmutable $dueDate = null,
        public ?\DateTimeImmutable $completedAt = null,
    ) {}
}
