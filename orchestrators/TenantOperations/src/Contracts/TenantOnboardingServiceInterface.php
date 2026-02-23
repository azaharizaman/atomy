<?php

declare(strict_types=1);

namespace Nexus\TenantOperations\Contracts;

use Nexus\TenantOperations\DTOs\TenantOnboardingRequest;
use Nexus\TenantOperations\DTOs\TenantOnboardingResult;

/**
 * Service interface for tenant onboarding operations.
 */
interface TenantOnboardingServiceInterface
{
    /**
     * Execute the full onboarding workflow.
     */
    public function onboard(TenantOnboardingRequest $request): TenantOnboardingResult;

    /**
     * Create the tenant entity.
     */
    public function createTenant(TenantOnboardingRequest $request): string;

    /**
     * Initialize tenant settings.
     */
    public function initializeSettings(string $tenantId, TenantOnboardingRequest $request): void;

    /**
     * Configure feature flags.
     */
    public function configureFeatures(string $tenantId, TenantOnboardingRequest $request): void;

    /**
     * Create company structure.
     */
    public function createCompanyStructure(string $tenantId, TenantOnboardingRequest $request): string;

    /**
     * Create admin user.
     */
    public function createAdminUser(string $tenantId, TenantOnboardingRequest $request): string;
}
