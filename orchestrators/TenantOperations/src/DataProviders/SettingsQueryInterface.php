<?php

declare(strict_types=1);

namespace Nexus\TenantOperations\DataProviders;

/**
 * Interface for querying settings data.
 */
interface SettingsQueryInterface
{
    /**
     * @return array<string, mixed>
     */
    public function getSettings(string $tenantId, ?string $key = null): array;
}
