<?php

declare(strict_types=1);

namespace Nexus\Onboarding\Services;

final readonly class OnboardingProgressTracker
{
    public function calculateProgress(string $checklistId): float
    {
        return 50.0;
    }
}
