<?php

declare(strict_types=1);

namespace Nexus\TenantOperations\DataProviders;

use Nexus\TenantOperations\Contracts\TenantContextProviderInterface;
use Nexus\TenantOperations\DTOs\TenantContext;

/**
 * Data provider for tenant context aggregation.
 * 
 * Aggregates tenant data from multiple packages into a unified context.
 */
final readonly class TenantContextDataProvider implements TenantContextProviderInterface
{
    public function __construct(
        private TenantQueryInterface $tenantQuery,
        private SettingsQueryInterface $settingsQuery,
        private FeatureQueryInterface $featureQuery,
    ) {}

    public function getContext(string $tenantId): TenantContext
    {
        $tenant = $this->tenantQuery->findById($tenantId);
        
        if ($tenant === null) {
            return new TenantContext(
                tenantId: null,
                tenantCode: null,
                tenantName: null,
                status: null,
                settings: [],
                features: [],
                plan: null,
            );
        }

        $settings = $this->settingsQuery->getSettings($tenantId);
        $features = $this->featureQuery->getFeatures($tenantId);

        return new TenantContext(
            tenantId: $tenant['id'],
            tenantCode: $tenant['code'],
            tenantName: $tenant['name'],
            status: $tenant['status'],
            settings: $settings,
            features: $features,
            plan: $tenant['plan'] ?? null,
        );
    }

    public function tenantExists(string $tenantId): bool
    {
        return $this->tenantQuery->exists($tenantId);
    }
}
