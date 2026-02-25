<?php

declare(strict_types=1);

namespace Nexus\TenantOperations\DataProviders;

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
