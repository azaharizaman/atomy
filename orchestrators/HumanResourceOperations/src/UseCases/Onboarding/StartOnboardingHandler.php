<?php

declare(strict_types=1);

namespace Nexus\HumanResourceOperations\UseCases\Onboarding;

use Nexus\Onboarding\Contracts\OnboardingProcessRepositoryInterface;

final readonly class StartOnboardingHandler
{
    public function __construct(
        private OnboardingProcessRepositoryInterface $onboardingRepository
    ) {}
    
    public function handle(string $employeeId): void
    {
        // Start onboarding process for new hire
        throw new \RuntimeException('Implementation pending');
    }
}
