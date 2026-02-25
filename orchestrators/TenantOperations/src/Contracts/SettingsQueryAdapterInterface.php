<?php

declare(strict_types=1);

namespace Nexus\TenantOperations\Contracts;

/**
 * Interface for querying settings data.
 */
interface SettingsQueryAdapterInterface
{
    /**
     * @return array<string, mixed>
     */
    public function getSettings(string $tenantId): array;
}
