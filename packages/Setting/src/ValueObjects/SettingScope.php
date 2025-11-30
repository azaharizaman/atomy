<?php

declare(strict_types=1);

namespace Nexus\Setting\ValueObjects;

/**
 * Represents a setting scope with its identifier.
 *
 * This immutable value object encapsulates the scope type and scope ID
 * for a setting, ensuring type safety and preventing invalid state.
 */
final readonly class SettingScope
{
    /**
     * Create a new setting scope.
     *
     * @param SettingLayer $layer The layer this scope belongs to
     * @param string|null $scopeId The identifier for the scope (user ID, tenant ID, or null for app)
     */
    public function __construct(
        public SettingLayer $layer,
        public ?string $scopeId = null,
    ) {
    }

    /**
     * Create a user scope instance.
     */
    public static function user(string $userId): self
    {
        return new self(SettingLayer::USER, $userId);
    }

    /**
     * Create a tenant scope instance.
     */
    public static function tenant(string $tenantId): self
    {
        return new self(SettingLayer::TENANT, $tenantId);
    }

    /**
     * Create an application scope instance.
     */
    public static function application(): self
    {
        return new self(SettingLayer::APPLICATION, null);
    }

    /**
     * Get the cache key for this scope.
     */
    public function cacheKey(string $settingKey): string
    {
        $prefix = "setting:{$this->layer->value}";

        if ($this->scopeId !== null) {
            return "{$prefix}:{$this->scopeId}:{$settingKey}";
        }

        return "{$prefix}:{$settingKey}";
    }

    /**
     * Check if this scope is writable.
     */
    public function isWritable(): bool
    {
        return $this->layer->isWritable();
    }

    /**
     * Convert to string representation.
     */
    public function toString(): string
    {
        if ($this->scopeId !== null) {
            return "{$this->layer->value}:{$this->scopeId}";
        }

        return $this->layer->value;
    }

    /**
     * Check equality with another scope.
     */
    public function equals(self $other): bool
    {
        return $this->layer === $other->layer
            && $this->scopeId === $other->scopeId;
    }
}
