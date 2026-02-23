<?php

declare(strict_types=1);

namespace Nexus\TenantOperations\Contracts;

/**
 * Interface for providing tenant settings data.
 */
interface TenantSettingsProviderInterface
{
    /**
     * Get tenant settings by key.
     *
     * @return array<string, mixed>
     */
    public function getSettings(string $tenantId, ?string $key = null): array;

    /**
     * Check if a setting exists.
     */
    public function settingExists(string $tenantId, string $key): bool;

    /**
     * Get default settings for a tenant plan.
     *
     * @return array<string, mixed>
     */
    public function getDefaultSettings(string $plan): array;
}
