<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Contracts;

/**
 * Adapter interface for settings management integration.
 *
 * This interface defines what the ProcurementOperations orchestrator needs
 * from a settings management service. Consuming applications must provide
 * a concrete implementation that adapts their chosen settings manager.
 *
 * @package Nexus\ProcurementOperations\Contracts
 */
interface SettingsAdapterInterface
{
    /**
     * Get a setting value.
     *
     * @param string $key The setting key (dot notation supported)
     * @param mixed $default Default value if setting not found
     * @param string|null $tenantId Tenant ID for tenant-scoped settings
     * @return mixed The setting value
     */
    public function get(string $key, mixed $default = null, ?string $tenantId = null): mixed;

    /**
     * Get a string setting value.
     *
     * @param string $key The setting key
     * @param string $default Default value if setting not found
     * @param string|null $tenantId Tenant ID for tenant-scoped settings
     * @return string The setting value as string
     */
    public function getString(string $key, string $default = '', ?string $tenantId = null): string;

    /**
     * Get an integer setting value.
     *
     * @param string $key The setting key
     * @param int $default Default value if setting not found
     * @param string|null $tenantId Tenant ID for tenant-scoped settings
     * @return int The setting value as integer
     */
    public function getInt(string $key, int $default = 0, ?string $tenantId = null): int;

    /**
     * Get a float setting value.
     *
     * @param string $key The setting key
     * @param float $default Default value if setting not found
     * @param string|null $tenantId Tenant ID for tenant-scoped settings
     * @return float The setting value as float
     */
    public function getFloat(string $key, float $default = 0.0, ?string $tenantId = null): float;

    /**
     * Get a boolean setting value.
     *
     * @param string $key The setting key
     * @param bool $default Default value if setting not found
     * @param string|null $tenantId Tenant ID for tenant-scoped settings
     * @return bool The setting value as boolean
     */
    public function getBool(string $key, bool $default = false, ?string $tenantId = null): bool;

    /**
     * Get an array setting value.
     *
     * @param string $key The setting key
     * @param array<mixed> $default Default value if setting not found
     * @param string|null $tenantId Tenant ID for tenant-scoped settings
     * @return array<mixed> The setting value as array
     */
    public function getArray(string $key, array $default = [], ?string $tenantId = null): array;

    /**
     * Set a setting value.
     *
     * @param string $key The setting key
     * @param mixed $value The value to set
     * @param string|null $tenantId Tenant ID for tenant-scoped settings
     */
    public function set(string $key, mixed $value, ?string $tenantId = null): void;

    /**
     * Check if a setting exists.
     *
     * @param string $key The setting key
     * @param string|null $tenantId Tenant ID for tenant-scoped settings
     * @return bool True if setting exists
     */
    public function has(string $key, ?string $tenantId = null): bool;

    /**
     * Delete a setting.
     *
     * @param string $key The setting key
     * @param string|null $tenantId Tenant ID for tenant-scoped settings
     */
    public function delete(string $key, ?string $tenantId = null): void;
}
