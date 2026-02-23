<?php

declare(strict_types=1);

namespace Nexus\SettingsManagement\Contracts;

/**
 * Interface for feature flag data provider.
 * Aggregates feature flags from the FeatureFlags package.
 */
interface FeatureFlagProviderInterface
{
    /**
     * Get feature flag by key for a tenant.
     */
    public function getFlag(string $flagKey, string $tenantId): ?array;

    /**
     * Get all feature flags for a tenant.
     */
    public function getAllFlags(string $tenantId): array;

    /**
     * Evaluate feature flag for a context.
     */
    public function evaluateFlag(string $flagKey, string $tenantId, array $context = []): bool;

    /**
     * Check if feature flag exists.
     */
    public function flagExists(string $flagKey, string $tenantId): bool;

    /**
     * Get feature flag targeting rules.
     */
    public function getTargetingRules(string $flagKey, string $tenantId): array;

    /**
     * Check if flag is enabled.
     */
    public function isFlagEnabled(string $flagKey, string $tenantId): bool;
}
