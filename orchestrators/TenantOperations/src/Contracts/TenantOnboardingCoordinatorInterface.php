<?php

declare(strict_types=1);

namespace Nexus\TenantOperations\Contracts;

use Nexus\TenantOperations\DTOs\TenantOnboardingRequest;
use Nexus\TenantOperations\DTOs\TenantOnboardingResult;
use Nexus\TenantOperations\DTOs\ValidationResult;

/**
 * Coordinator interface for tenant onboarding operations.
 */
interface TenantOnboardingCoordinatorInterface extends TenantCoordinatorInterface
{
    /**
     * Onboard a new tenant with all required configurations.
     */
    public function onboard(TenantOnboardingRequest $request): TenantOnboardingResult;

    /**
     * Validate prerequisites before onboarding.
     */
    public function validatePrerequisites(TenantOnboardingRequest $request): ValidationResult;
}
