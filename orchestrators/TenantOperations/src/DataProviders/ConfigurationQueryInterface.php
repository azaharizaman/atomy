<?php

declare(strict_types=1);

namespace Nexus\TenantOperations\DataProviders;

/**
 * Interface for querying configuration.
 */
interface ConfigurationQueryInterface
{
    public function exists(string $tenantId, string $configKey): bool;
    public function get(string $tenantId, string $configKey): ?array;
}
