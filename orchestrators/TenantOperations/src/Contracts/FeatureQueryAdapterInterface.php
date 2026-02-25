<?php

declare(strict_types=1);

namespace Nexus\TenantOperations\Contracts;

/**
 * Interface for querying feature flags.
 */
interface FeatureQueryAdapterInterface
{
    public function isEnabled(string $tenantId, string $featureKey): bool;

    /**
     * @return array<string, bool>
     */
    public function getAll(string $tenantId): array;

    /**
     * @return array<string, bool>
     */
    public function getFeatures(string $tenantId): array;
}
