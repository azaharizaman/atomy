<?php

declare(strict_types=1);

namespace Nexus\TenantOperations\Contracts;

use Nexus\TenantOperations\DTOs\TenantCompanyOnboardingRequest;
use Nexus\TenantOperations\DTOs\TenantCompanyOnboardingResult;

/**
 * Service contract for the alpha company onboarding flow.
 */
interface TenantCompanyOnboardingServiceInterface
{
    /**
     * Execute the company onboarding workflow.
     */
    public function onboard(TenantCompanyOnboardingRequest $request): TenantCompanyOnboardingResult;
}
