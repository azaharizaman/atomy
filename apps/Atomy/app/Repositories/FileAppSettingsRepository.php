<?php

declare(strict_types=1);

namespace App\Repositories;

use Nexus\Setting\Contracts\SettingRepositoryInterface;
use Nexus\Setting\Exceptions\ReadOnlySettingException;

/**
 * File/config-backed repository for application-scoped settings.
 *
 * This repository reads settings from Laravel config files
 * and is read-only (no write operations allowed).
 */
class FileAppSettingsRepository implements SettingRepositoryInterface
{
    /**
     * Create a new repository instance.
     */
    public function __construct()
    {
    }

    /**
     * Retrieve a setting value by key.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        // Read from config using dot notation
        // e.g., 'app.timezone' maps to config('app.timezone')
        return config($key, $default);
    }

    /**
     * Store a setting value (not allowed for application layer).
     *
     * @throws ReadOnlySettingException Always throws as application layer is read-only
     */
    public function set(string $key, mixed $value): void
    {
        throw new ReadOnlySettingException('Application settings are read-only');
    }

    /**
     * Delete a setting by key (not allowed for application layer).
     *
     * @throws ReadOnlySettingException Always throws as application layer is read-only
     */
    public function delete(string $key): void
    {
        throw new ReadOnlySettingException('Application settings are read-only');
    }

    /**
     * Check if a setting exists.
     */
    public function has(string $key): bool
    {
        return config()->has($key);
    }

    /**
     * Retrieve all settings.
     *
     * Note: This returns all config values which may be extensive.
     * Consider filtering by specific config keys in production.
     */
    public function getAll(): array
    {
        return config()->all();
    }

    /**
     * Retrieve all settings matching a key prefix.
     */
    public function getByPrefix(string $prefix): array
    {
        $all = config()->all();
        $result = [];

        foreach ($all as $key => $value) {
            if (str_starts_with($key, $prefix)) {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * Retrieve setting metadata.
     *
     * Application settings typically don't have metadata in config files.
     */
    public function getMetadata(string $key): ?array
    {
        // Application settings don't have metadata
        return null;
    }

    /**
     * Bulk update multiple settings (not allowed for application layer).
     *
     * @throws ReadOnlySettingException Always throws as application layer is read-only
     */
    public function bulkSet(array $settings): void
    {
        throw new ReadOnlySettingException('Application settings are read-only');
    }

    /**
     * Check if this repository is writable.
     */
    public function isWritable(): bool
    {
        return false;
    }
}
