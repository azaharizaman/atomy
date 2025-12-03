<?php

declare(strict_types=1);

namespace Nexus\Onboarding\Contracts;

use Nexus\Onboarding\Entities\OnboardingChecklist;

interface OnboardingChecklistRepositoryInterface
{
    public function findById(string $id): ?OnboardingChecklist;
    public function findByEmployeeId(string $employeeId): ?OnboardingChecklist;
    public function save(OnboardingChecklist $checklist): void;
}
