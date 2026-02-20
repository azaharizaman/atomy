<?php

declare(strict_types=1);

namespace Nexus\Onboarding\Contracts;

use Nexus\Onboarding\Entities\OnboardingTask;

interface OnboardingTaskRepositoryInterface
{
    public function findById(string $id): ?OnboardingTask;
    public function findByChecklistId(string $checklistId): array;
    public function save(OnboardingTask $task): void;
}
