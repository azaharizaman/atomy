<?php

declare(strict_types=1);

namespace Nexus\SettingsManagement\Contracts;

/**
 * Interface for settings data provider.
 * Aggregates settings from the Setting package.
 */
interface SettingsProviderInterface
{
    /**
     * Get setting by key for a tenant.
     */
    public function getSetting(string $key, string $tenantId): ?array;

    /**
     * Get all settings for a tenant.
     */
    public function getAllSettings(string $tenantId): array;

    /**
     * Get setting value with hierarchy resolution (application, tenant, user).
     */
    public function resolveSettingValue(string $key, ?string $tenantId, ?string $userId): mixed;

    /**
     * Check if setting exists.
     */
    public function settingExists(string $key, string $tenantId): bool;

    /**
     * Get settings by category.
     */
    public function getSettingsByCategory(string $category, string $tenantId): array;
}
