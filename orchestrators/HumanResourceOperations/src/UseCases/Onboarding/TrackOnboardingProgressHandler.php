<?php

declare(strict_types=1);

namespace Nexus\HumanResourceOperations\UseCases\Onboarding;

use Nexus\Onboarding\Services\OnboardingProgressTracker;

final readonly class TrackOnboardingProgressHandler
{
    public function __construct(
        private OnboardingProgressTracker $progressTracker
    ) {}
    
    public function handle(string $employeeId): array
    {
        // Get onboarding progress status
        throw new \RuntimeException('Implementation pending');
    }
}
