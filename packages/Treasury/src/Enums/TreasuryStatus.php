<?php

declare(strict_types=1);

namespace Nexus\Treasury\Enums;

/**
 * Treasury status enum
 */
enum TreasuryStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case PENDING = 'pending';
    case SUSPENDED = 'suspended';
    case CLOSED = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::INACTIVE => 'Inactive',
            self::PENDING => 'Pending',
            self::SUSPENDED => 'Suspended',
            self::CLOSED => 'Closed',
        };
    }

    public function isOperational(): bool
    {
        return $this === self::ACTIVE;
    }
}
