<?php

declare(strict_types=1);

namespace Nexus\Setting\Services;

use Nexus\Setting\Contracts\SettingRepositoryInterface;
use Nexus\Setting\Contracts\SettingsCacheInterface;
use Nexus\Setting\Exceptions\ProtectedSettingException;
use Nexus\Setting\Exceptions\ReadOnlySettingException;
use Nexus\Setting\ValueObjects\SettingLayer;
use Nexus\Setting\ValueObjects\SettingScope;

/**
 * Settings manager service.
 *
 * This is the main service for managing settings across hierarchical layers.
 * It implements the core business logic for setting resolution, validation,
 * and cache management following the User → Tenant → Application cascade.
 */
class SettingsManager
{
    private readonly SettingsCacheManager $cacheManager;

    /**
     * Create a new settings manager instance.
     *
     * @param SettingRepositoryInterface $userRepository User-scoped settings repository
     * @param SettingRepositoryInterface $tenantRepository Tenant-scoped settings repository
     * @param SettingRepositoryInterface $applicationRepository Application-scoped settings repository
     * @param SettingsCacheInterface $cache Cache implementation
     * @param array<string> $protectedKeys List of protected setting keys
     */
    public function __construct(
        private readonly SettingRepositoryInterface $userRepository,
        private readonly SettingRepositoryInterface $tenantRepository,
        private readonly SettingRepositoryInterface $applicationRepository,
        SettingsCacheInterface $cache,
        private readonly array $protectedKeys = [],
    ) {
        $this->cacheManager = new SettingsCacheManager($cache);
    }

    /**
     * Get a setting value with hierarchical resolution.
     *
     * Resolution order: User → Tenant → Application → Default
     *
     * @param string $key The setting key
     * @param mixed $default The default value if not found
     * @param string|null $userId Optional user ID for user layer resolution
     * @param string|null $tenantId Optional tenant ID for tenant layer resolution
     * @return mixed The setting value or default
     */
    public function get(string $key, mixed $default = null, ?string $userId = null, ?string $tenantId = null): mixed
    {
        // Try user layer first (if userId provided)
        if ($userId !== null) {
            $scope = SettingScope::user($userId);
            $value = $this->cacheManager->rememberScoped(
                $scope,
                $key,
                fn () => $this->userRepository->get($key)
            );

            if ($value !== null) {
                return $value;
            }
        }

        // Try tenant layer (if tenantId provided)
        if ($tenantId !== null) {
            $scope = SettingScope::tenant($tenantId);
            $value = $this->cacheManager->rememberScoped(
                $scope,
                $key,
                fn () => $this->tenantRepository->get($key)
            );

            if ($value !== null) {
                return $value;
            }
        }

        // Try application layer
        $scope = SettingScope::application();
        $value = $this->cacheManager->rememberScoped(
            $scope,
            $key,
            fn () => $this->applicationRepository->get($key)
        );

        return $value ?? $default;
    }

    /**
     * Set a user-scoped setting.
     *
     * @param string $userId The user ID
     * @param string $key The setting key
     * @param mixed $value The setting value
     * @throws ReadOnlySettingException If setting is read-only
     * @throws ProtectedSettingException If setting is protected
     */
    public function setUserSetting(string $userId, string $key, mixed $value): void
    {
        $this->validateNotProtected($key, SettingLayer::USER);
        $this->userRepository->set($key, $value);

        // Invalidate cache
        $scope = SettingScope::user($userId);
        $this->cacheManager->forgetScoped($scope, $key);
    }

    /**
     * Set a tenant-scoped setting.
     *
     * @param string $tenantId The tenant ID
     * @param string $key The setting key
     * @param mixed $value The setting value
     * @throws ReadOnlySettingException If setting is read-only
     * @throws ProtectedSettingException If setting is protected
     */
    public function setTenantSetting(string $tenantId, string $key, mixed $value): void
    {
        $this->validateNotProtected($key, SettingLayer::TENANT);
        $this->tenantRepository->set($key, $value);

        // Invalidate cache
        $scope = SettingScope::tenant($tenantId);
        $this->cacheManager->forgetScoped($scope, $key);
    }

    /**
     * Delete a user-scoped setting.
     *
     * @param string $userId The user ID
     * @param string $key The setting key
     * @throws ReadOnlySettingException If setting is read-only
     */
    public function deleteUserSetting(string $userId, string $key): void
    {
        $this->userRepository->delete($key);

        // Invalidate cache
        $scope = SettingScope::user($userId);
        $this->cacheManager->forgetScoped($scope, $key);
    }

    /**
     * Delete a tenant-scoped setting.
     *
     * @param string $tenantId The tenant ID
     * @param string $key The setting key
     * @throws ReadOnlySettingException If setting is read-only
     */
    public function deleteTenantSetting(string $tenantId, string $key): void
    {
        $this->tenantRepository->delete($key);

        // Invalidate cache
        $scope = SettingScope::tenant($tenantId);
        $this->cacheManager->forgetScoped($scope, $key);
    }

    /**
     * Get all settings for a specific user.
     *
     * @param string $userId The user ID
     * @return array<string, mixed> All user settings
     */
    public function getAllUserSettings(string $userId): array
    {
        return $this->userRepository->getAll();
    }

    /**
     * Get all settings for a specific tenant.
     *
     * @param string $tenantId The tenant ID
     * @return array<string, mixed> All tenant settings
     */
    public function getAllTenantSettings(string $tenantId): array
    {
        return $this->tenantRepository->getAll();
    }

    /**
     * Get all settings matching a key prefix.
     *
     * @param string $prefix The key prefix
     * @param string|null $userId Optional user ID
     * @param string|null $tenantId Optional tenant ID
     * @return array<string, mixed> Matching settings with hierarchical resolution
     */
    public function getByPrefix(string $prefix, ?string $userId = null, ?string $tenantId = null): array
    {
        $result = [];

        // Get from all layers
        $appSettings = $this->applicationRepository->getByPrefix($prefix);
        $tenantSettings = $tenantId !== null ? $this->tenantRepository->getByPrefix($prefix) : [];
        $userSettings = $userId !== null ? $this->userRepository->getByPrefix($prefix) : [];

        // Merge with priority (user > tenant > application)
        $result = array_merge($appSettings, $tenantSettings, $userSettings);

        return $result;
    }

    /**
     * Get setting value as string.
     *
     * @param string $key The setting key
     * @param string $default The default value
     * @param string|null $userId Optional user ID
     * @param string|null $tenantId Optional tenant ID
     * @return string The setting value as string
     */
    public function getString(string $key, string $default = '', ?string $userId = null, ?string $tenantId = null): string
    {
        $value = $this->get($key, $default, $userId, $tenantId);

        return (string) $value;
    }

    /**
     * Get setting value as integer.
     *
     * @param string $key The setting key
     * @param int $default The default value
     * @param string|null $userId Optional user ID
     * @param string|null $tenantId Optional tenant ID
     * @return int The setting value as integer
     */
    public function getInt(string $key, int $default = 0, ?string $userId = null, ?string $tenantId = null): int
    {
        $value = $this->get($key, $default, $userId, $tenantId);

        return (int) $value;
    }

    /**
     * Get setting value as boolean.
     *
     * @param string $key The setting key
     * @param bool $default The default value
     * @param string|null $userId Optional user ID
     * @param string|null $tenantId Optional tenant ID
     * @return bool The setting value as boolean
     */
    public function getBool(string $key, bool $default = false, ?string $userId = null, ?string $tenantId = null): bool
    {
        $value = $this->get($key, $default, $userId, $tenantId);

        return (bool) $value;
    }

    /**
     * Get setting value as float.
     *
     * @param string $key The setting key
     * @param float $default The default value
     * @param string|null $userId Optional user ID
     * @param string|null $tenantId Optional tenant ID
     * @return float The setting value as float
     */
    public function getFloat(string $key, float $default = 0.0, ?string $userId = null, ?string $tenantId = null): float
    {
        $value = $this->get($key, $default, $userId, $tenantId);

        return (float) $value;
    }

    /**
     * Get setting value as array.
     *
     * @param string $key The setting key
     * @param array<mixed> $default The default value
     * @param string|null $userId Optional user ID
     * @param string|null $tenantId Optional tenant ID
     * @return array<mixed> The setting value as array
     */
    public function getArray(string $key, array $default = [], ?string $userId = null, ?string $tenantId = null): array
    {
        $value = $this->get($key, $default, $userId, $tenantId);

        if (! is_array($value)) {
            return $default;
        }

        return $value;
    }

    /**
     * Bulk update multiple settings in a single transaction.
     *
     * @param array<string, mixed> $settings Key-value pairs to update
     * @param SettingLayer $layer The layer to update (user or tenant)
     * @param string $scopeId The scope ID (user ID or tenant ID)
     * @throws ReadOnlySettingException If any setting is read-only
     * @throws ProtectedSettingException If any setting is protected
     */
    public function bulkSet(array $settings, SettingLayer $layer, string $scopeId): void
    {
        $repository = match ($layer) {
            SettingLayer::USER => $this->userRepository,
            SettingLayer::TENANT => $this->tenantRepository,
            SettingLayer::APPLICATION => throw new ReadOnlySettingException('application layer'),
        };

        // Validate all settings before persisting
        foreach (array_keys($settings) as $key) {
            $this->validateNotProtected($key, $layer);
        }

        // Bulk persist
        $repository->bulkSet($settings);

        // Invalidate cache for all updated settings
        $scope = match ($layer) {
            SettingLayer::USER => SettingScope::user($scopeId),
            SettingLayer::TENANT => SettingScope::tenant($scopeId),
            default => SettingScope::application(),
        };

        $this->cacheManager->forgetScope($scope);
    }

    /**
     * Check if a setting exists in any layer.
     *
     * @param string $key The setting key
     * @param string|null $userId Optional user ID
     * @param string|null $tenantId Optional tenant ID
     * @return bool True if setting exists
     */
    public function has(string $key, ?string $userId = null, ?string $tenantId = null): bool
    {
        if ($userId !== null && $this->userRepository->has($key)) {
            return true;
        }

        if ($tenantId !== null && $this->tenantRepository->has($key)) {
            return true;
        }

        return $this->applicationRepository->has($key);
    }

    /**
     * Get the origin layer of a setting.
     *
     * @param string $key The setting key
     * @param string|null $userId Optional user ID
     * @param string|null $tenantId Optional tenant ID
     * @return string|null The layer name ('user', 'tenant', 'application') or null if not found
     */
    public function getOrigin(string $key, ?string $userId = null, ?string $tenantId = null): ?string
    {
        if ($userId !== null && $this->userRepository->has($key)) {
            return SettingLayer::USER->value;
        }

        if ($tenantId !== null && $this->tenantRepository->has($key)) {
            return SettingLayer::TENANT->value;
        }

        if ($this->applicationRepository->has($key)) {
            return SettingLayer::APPLICATION->value;
        }

        return null;
    }

    /**
     * Export all settings for a specific tenant (for backup/migration).
     *
     * @param string $tenantId The tenant ID
     * @return array<string, mixed> All tenant settings
     */
    public function export(string $tenantId): array
    {
        return $this->tenantRepository->getAll();
    }

    /**
     * Import settings from exported data (for restore/migration).
     *
     * @param array<string, mixed> $data Settings data to import
     * @param string $tenantId The tenant ID
     * @throws ReadOnlySettingException If any setting is read-only
     */
    public function import(array $data, string $tenantId): void
    {
        $this->bulkSet($data, SettingLayer::TENANT, $tenantId);
    }

    /**
     * Get setting metadata.
     *
     * @param string $key The setting key
     * @return array<string, mixed>|null Metadata or null if not found
     */
    public function getMetadata(string $key): ?array
    {
        // Try to get metadata from any layer (application layer typically defines it)
        return $this->applicationRepository->getMetadata($key)
            ?? $this->tenantRepository->getMetadata($key)
            ?? $this->userRepository->getMetadata($key);
    }

    /**
     * Validate that a setting is not protected before modification.
     *
     * @throws ProtectedSettingException If setting is protected
     */
    private function validateNotProtected(string $key, SettingLayer $layer): void
    {
        if (in_array($key, $this->protectedKeys, true)) {
            throw new ProtectedSettingException($key, $layer->value);
        }
    }

    /**
     * Get the cache manager instance.
     */
    public function getCacheManager(): SettingsCacheManager
    {
        return $this->cacheManager;
    }
}
