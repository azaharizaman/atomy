<?php

declare(strict_types=1);

namespace Nexus\Identity\ValueObjects;

/**
 * User status value object
 * 
 * Represents the status of a user account
 */
enum UserStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case SUSPENDED = 'suspended';
    case LOCKED = 'locked';
    case PENDING_ACTIVATION = 'pending_activation';

    /**
     * Check if user can authenticate with this status
     */
    public function canAuthenticate(): bool
    {
        return match($this) {
            self::ACTIVE => true,
            default => false,
        };
    }

    /**
     * Check if status requires administrator intervention
     */
    public function requiresAdminIntervention(): bool
    {
        return match($this) {
            self::LOCKED, self::SUSPENDED => true,
            default => false,
        };
    }

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match($this) {
            self::ACTIVE => 'Active',
            self::INACTIVE => 'Inactive',
            self::SUSPENDED => 'Suspended',
            self::LOCKED => 'Locked',
            self::PENDING_ACTIVATION => 'Pending Activation',
        };
    }
}
