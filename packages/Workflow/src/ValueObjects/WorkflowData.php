<?php

declare(strict_types=1);

namespace Nexus\Workflow\ValueObjects;

/**
 * Immutable workflow data container.
 *
 * Wraps workflow data with type-safe access.
 */
final readonly class WorkflowData
{
    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        private array $data
    ) {}

    /**
     * Get data value by key.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    /**
     * Check if key exists.
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }

    /**
     * Get all data as array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->data;
    }

    /**
     * Create new instance with additional data.
     *
     * @param array<string, mixed> $additionalData
     */
    public function with(array $additionalData): self
    {
        return new self(array_merge($this->data, $additionalData));
    }

    /**
     * Create new instance without specified keys.
     *
     * @param string[] $keys
     */
    public function without(array $keys): self
    {
        return new self(array_diff_key($this->data, array_flip($keys)));
    }
}
