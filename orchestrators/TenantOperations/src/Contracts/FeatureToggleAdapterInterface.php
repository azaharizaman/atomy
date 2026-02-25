<?php

declare(strict_types=1);

namespace Nexus\TenantOperations\Contracts;

/**
 * Interface for toggling feature flags.
 */
interface FeatureToggleAdapterInterface
{
    public function enable(string $tenantId, string $featureKey): bool;
    public function disable(string $tenantId, string $featureKey): bool;
}
