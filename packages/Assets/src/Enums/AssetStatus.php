<?php

declare(strict_types=1);

namespace Nexus\Assets\Enums;

/**
 * Asset Status Enum
 *
 * Defines the lifecycle states of a fixed asset.
 */
enum AssetStatus: string
{
    case DRAFT = 'draft';
    case ACTIVE = 'active';
    case IN_USE = 'in_use';
    case UNDER_MAINTENANCE = 'under_maintenance';
    case DISPOSED = 'disposed';
    case RETIRED = 'retired';

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match($this) {
            self::DRAFT => 'Draft',
            self::ACTIVE => 'Active',
            self::IN_USE => 'In Use',
            self::UNDER_MAINTENANCE => 'Under Maintenance',
            self::DISPOSED => 'Disposed',
            self::RETIRED => 'Retired (Fully Depreciated)',
        };
    }

    /**
     * Check if asset can be depreciated in this status
     */
    public function canDepreciate(): bool
    {
        return match($this) {
            self::ACTIVE, self::IN_USE, self::UNDER_MAINTENANCE => true,
            default => false,
        };
    }

    /**
     * Check if asset can be disposed
     */
    public function canDispose(): bool
    {
        return match($this) {
            self::ACTIVE, self::IN_USE, self::UNDER_MAINTENANCE, self::RETIRED => true,
            default => false,
        };
    }

    /**
     * Check if asset is in an active state
     */
    public function isActive(): bool
    {
        return match($this) {
            self::ACTIVE, self::IN_USE, self::UNDER_MAINTENANCE => true,
            default => false,
        };
    }

    /**
     * Get next logical status transitions
     */
    public function getAllowedTransitions(): array
    {
        return match($this) {
            self::DRAFT => [self::ACTIVE],
            self::ACTIVE => [self::IN_USE, self::UNDER_MAINTENANCE, self::DISPOSED, self::RETIRED],
            self::IN_USE => [self::ACTIVE, self::UNDER_MAINTENANCE, self::DISPOSED, self::RETIRED],
            self::UNDER_MAINTENANCE => [self::ACTIVE, self::IN_USE, self::DISPOSED],
            self::DISPOSED => [],
            self::RETIRED => [self::DISPOSED],
        };
    }
}
