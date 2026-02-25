<?php

declare(strict_types=1);

namespace Nexus\TenantOperations\Contracts;

/**
 * Adapter interface for configuring tenant features.
 * 
 * Must be implemented by Layer 3 (Adapters) using Nexus\FeatureFlags package.
 */
interface FeatureConfiguratorAdapterInterface
{
    /**
     * Configure features for a tenant.
     *
     * @param string $tenantId
     * @param array<string, bool> $features
     * @return void
     */
    public function configure(string $tenantId, array $features): void;
}
