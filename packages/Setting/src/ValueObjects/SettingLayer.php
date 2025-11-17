<?php

declare(strict_types=1);

namespace Nexus\Setting\ValueObjects;

/**
 * Represents a setting layer in the hierarchical resolution system.
 *
 * Layers define the priority order for setting resolution:
 * - USER: Highest priority (user-specific overrides)
 * - TENANT: Mid priority (organization/tenant defaults)
 * - APPLICATION: Lowest priority (system/environment defaults, read-only)
 */
enum SettingLayer: string
{
    case USER = 'user';
    case TENANT = 'tenant';
    case APPLICATION = 'application';

    /**
     * Get all layers in resolution order (highest to lowest priority).
     *
     * @return array<self>
     */
    public static function resolutionOrder(): array
    {
        return [
            self::USER,
            self::TENANT,
            self::APPLICATION,
        ];
    }

    /**
     * Check if this layer is writable (not read-only).
     */
    public function isWritable(): bool
    {
        return match ($this) {
            self::USER, self::TENANT => true,
            self::APPLICATION => false,
        };
    }

    /**
     * Get the priority level (higher number = higher priority).
     */
    public function priority(): int
    {
        return match ($this) {
            self::USER => 3,
            self::TENANT => 2,
            self::APPLICATION => 1,
        };
    }
}
