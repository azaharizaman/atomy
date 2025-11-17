<?php

declare(strict_types=1);

namespace Nexus\Setting\Contracts;

/**
 * Repository contract for setting persistence operations.
 *
 * This interface defines the persistence contract for settings at each layer
 * (user, tenant, application). Implementations are responsible for:
 * - User layer: Database-backed repository for user-specific settings
 * - Tenant layer: Database-backed repository for tenant-specific settings
 * - Application layer: File/environment-backed repository for system defaults
 */
interface SettingRepositoryInterface
{
    /**
     * Retrieve a setting value by key.
     *
     * @param string $key The setting key
     * @param mixed $default The default value if setting not found
     * @return mixed The setting value or default
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Store a setting value.
     *
     * @param string $key The setting key
     * @param mixed $value The setting value
     * @throws \Nexus\Setting\Exceptions\ReadOnlySettingException If setting is read-only
     * @throws \Nexus\Setting\Exceptions\ProtectedSettingException If setting is protected
     */
    public function set(string $key, mixed $value): void;

    /**
     * Delete a setting by key.
     *
     * @param string $key The setting key
     * @throws \Nexus\Setting\Exceptions\ReadOnlySettingException If setting is read-only
     */
    public function delete(string $key): void;

    /**
     * Check if a setting exists.
     *
     * @param string $key The setting key
     * @return bool True if setting exists
     */
    public function has(string $key): bool;

    /**
     * Retrieve all settings.
     *
     * @return array<string, mixed> All settings as key-value pairs
     */
    public function getAll(): array;

    /**
     * Retrieve all settings matching a key prefix.
     *
     * @param string $prefix The key prefix to match
     * @return array<string, mixed> Matching settings as key-value pairs
     */
    public function getByPrefix(string $prefix): array;

    /**
     * Retrieve setting metadata (type, description, validation rules).
     *
     * @param string $key The setting key
     * @return array<string, mixed>|null Metadata array or null if not found
     */
    public function getMetadata(string $key): ?array;

    /**
     * Bulk update multiple settings in a single transaction.
     *
     * @param array<string, mixed> $settings Key-value pairs to update
     * @throws \Nexus\Setting\Exceptions\ReadOnlySettingException If any setting is read-only
     */
    public function bulkSet(array $settings): void;

    /**
     * Check if this repository is writable (application layer is read-only).
     *
     * @return bool True if writable
     */
    public function isWritable(): bool;
}
