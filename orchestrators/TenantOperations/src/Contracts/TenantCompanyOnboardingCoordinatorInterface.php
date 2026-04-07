<?php

declare(strict_types=1);

namespace Nexus\TenantOperations\Contracts;

use Nexus\TenantOperations\DTOs\TenantCompanyOnboardingRequest;
use Nexus\TenantOperations\DTOs\TenantCompanyOnboardingResult;

/**
 * Alpha-facing coordinator for minimal company onboarding.
 */
interface TenantCompanyOnboardingCoordinatorInterface
{
    /**
     * Create a new company/tenant and its first owner user.
     */
    public function onboard(TenantCompanyOnboardingRequest $request): TenantCompanyOnboardingResult;
}
