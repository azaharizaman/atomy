<?php

declare(strict_types=1);

namespace Nexus\TenantOperations\Contracts;

/**
 * Interface for providing tenant feature flag data.
 */
interface TenantFeatureProviderInterface
{
    /**
     * Check if a feature is enabled for a tenant.
     */
    public function isFeatureEnabled(string $tenantId, string $featureKey): bool;

    /**
     * Get all enabled features for a tenant.
     *
     * @return array<string, bool>
     */
    public function getEnabledFeatures(string $tenantId): array;

    /**
     * Get feature flags for a tenant plan.
     *
     * @return array<string, bool>
     */
    public function getDefaultFeatures(string $plan): array;

    /**
     * Enable a feature for a tenant.
     */
    public function enableFeature(string $tenantId, string $featureKey): bool;

    /**
     * Disable a feature for a tenant.
     */
    public function disableFeature(string $tenantId, string $featureKey): bool;
}
