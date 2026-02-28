<?php

declare(strict_types=1);

namespace Nexus\SettingsManagement\Contracts;

/**
 * Interface for settings persistence provider.
 * Used by orchestrator to update settings in the underlying package.
 */
interface SettingsPersistProviderInterface
{
    /**
     * Update or create a setting for a tenant.
     */
    public function update(string $key, mixed $value, ?string $tenantId = null, ?string $userId = null): void;

    /**
     * Delete a setting for a tenant.
     */
    public function delete(string $key, ?string $tenantId = null, ?string $userId = null): void;

    /**
     * Bulk update multiple settings.
     */
    public function bulkUpdate(array $settings, ?string $tenantId = null, ?string $userId = null): void;
}
