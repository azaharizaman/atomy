<?php

declare(strict_types=1);

namespace Nexus\Setting\Contracts;

/**
 * Schema registry contract for setting validation rules.
 *
 * This interface defines the contract for managing setting schemas
 * which describe the structure, type, and validation rules for settings.
 */
interface SettingsSchemaRegistryInterface
{
    /**
     * Register a setting schema.
     *
     * @param string $key The setting key
     * @param array<string, mixed> $schema The schema definition
     */
    public function register(string $key, array $schema): void;

    /**
     * Get the schema for a setting.
     *
     * @param string $key The setting key
     * @return array<string, mixed>|null The schema or null if not found
     */
    public function get(string $key): ?array;

    /**
     * Check if a schema exists for a setting.
     *
     * @param string $key The setting key
     * @return bool True if schema exists
     */
    public function has(string $key): bool;

    /**
     * Get all registered schemas.
     *
     * @return array<string, array<string, mixed>> All schemas indexed by key
     */
    public function all(): array;

    /**
     * Unregister a setting schema.
     *
     * @param string $key The setting key
     */
    public function unregister(string $key): void;

    /**
     * Get all schemas for a specific group.
     *
     * @param string $group The group name
     * @return array<string, array<string, mixed>> All schemas in the group
     */
    public function getByGroup(string $group): array;
}
