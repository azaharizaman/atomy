<?php

declare(strict_types=1);

namespace Nexus\TenantOperations\DataProviders;

use Nexus\TenantOperations\Contracts\TenantFeatureProviderInterface;

/**
 * Data provider for tenant feature flags.
 */
final readonly class TenantFeatureDataProvider implements TenantFeatureProviderInterface
{
    public function __construct(
        private FeatureQueryInterface $featureQuery,
        private FeatureToggleInterface $featureToggle,
    ) {}

    public function isFeatureEnabled(string $tenantId, string $featureKey): bool
    {
        return $this->featureQuery->isEnabled($tenantId, $featureKey);
    }

    /**
     * @return array<string, bool>
     */
    public function getEnabledFeatures(string $tenantId): array
    {
        return $this->featureQuery->getAll($tenantId);
    }

    /**
     * @return array<string, bool>
     */
    public function getDefaultFeatures(string $plan): array
    {
        return match ($plan) {
            'starter' => [
                'finance' => true,
                'hr' => true,
                'sales' => false,
                'procurement' => false,
                'inventory' => false,
                'crm' => false,
                'advanced_reporting' => false,
                'api_access' => false,
            ],
            'professional' => [
                'finance' => true,
                'hr' => true,
                'sales' => true,
                'procurement' => true,
                'inventory' => true,
                'crm' => false,
                'advanced_reporting' => true,
                'api_access' => true,
            ],
            'enterprise' => [
                'finance' => true,
                'hr' => true,
                'sales' => true,
                'procurement' => true,
                'inventory' => true,
                'crm' => true,
                'advanced_reporting' => true,
                'api_access' => true,
                'custom_branding' => true,
                'multi_entity' => true,
            ],
            default => [],
        };
    }

    public function enableFeature(string $tenantId, string $featureKey): bool
    {
        return $this->featureToggle->enable($tenantId, $featureKey);
    }

    public function disableFeature(string $tenantId, string $featureKey): bool
    {
        return $this->featureToggle->disable($tenantId, $featureKey);
    }
}

/**
 * Interface for querying feature flags.
 */
interface FeatureQueryInterface
{
    public function isEnabled(string $tenantId, string $featureKey): bool;

    /**
     * @return array<string, bool>
     */
    public function getAll(string $tenantId): array;
}

/**
 * Interface for toggling feature flags.
 */
interface FeatureToggleInterface
{
    public function enable(string $tenantId, string $featureKey): bool;
    public function disable(string $tenantId, string $featureKey): bool;
}
