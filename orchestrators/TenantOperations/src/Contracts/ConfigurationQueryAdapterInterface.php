<?php

declare(strict_types=1);

namespace Nexus\TenantOperations\Contracts;

/**
 * Interface for querying configuration.
 */
interface ConfigurationQueryAdapterInterface
{
    public function exists(string $tenantId, string $configKey): bool;
    public function get(string $tenantId, string $configKey): ?array;
}
