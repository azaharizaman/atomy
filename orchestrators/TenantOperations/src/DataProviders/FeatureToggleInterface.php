<?php

declare(strict_types=1);

namespace Nexus\TenantOperations\DataProviders;

/**
 * Interface for toggling feature flags.
 */
interface FeatureToggleInterface
{
    public function enable(string $tenantId, string $featureKey): bool;
    public function disable(string $tenantId, string $featureKey): bool;
}
