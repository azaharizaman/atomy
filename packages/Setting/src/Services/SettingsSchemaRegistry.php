<?php

declare(strict_types=1);

namespace Nexus\Setting\Services;

use Nexus\Setting\Contracts\SettingsSchemaRegistryInterface;

/**
 * Settings schema registry service.
 *
 * This service manages setting schemas which define the structure,
 * type, and validation rules for settings.
 */
class SettingsSchemaRegistry implements SettingsSchemaRegistryInterface
{
    /**
     * @var array<string, array<string, mixed>>
     */
    private array $schemas = [];

    /**
     * Register a setting schema.
     */
    public function register(string $key, array $schema): void
    {
        $this->schemas[$key] = $schema;
    }

    /**
     * Get the schema for a setting.
     */
    public function get(string $key): ?array
    {
        return $this->schemas[$key] ?? null;
    }

    /**
     * Check if a schema exists for a setting.
     */
    public function has(string $key): bool
    {
        return isset($this->schemas[$key]);
    }

    /**
     * Get all registered schemas.
     */
    public function all(): array
    {
        return $this->schemas;
    }

    /**
     * Unregister a setting schema.
     */
    public function unregister(string $key): void
    {
        unset($this->schemas[$key]);
    }

    /**
     * Get all schemas for a specific group.
     */
    public function getByGroup(string $group): array
    {
        return array_filter(
            $this->schemas,
            fn (array $schema) => ($schema['group'] ?? null) === $group
        );
    }
}
