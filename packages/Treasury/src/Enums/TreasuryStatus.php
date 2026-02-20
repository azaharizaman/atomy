<?php

declare(strict_types=1);

namespace Nexus\Treasury\Enums;

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

    public function isActive(): bool
    {
        return $this === self::ACTIVE;
    }

    public function isInactive(): bool
    {
        return $this === self::INACTIVE;
    }

    public function isPending(): bool
    {
        return $this === self::PENDING;
    }

    public function isSuspended(): bool
    {
        return $this === self::SUSPENDED;
    }

    public function isClosed(): bool
    {
        return $this === self::CLOSED;
    }

    public function canTransitionTo(self $status): bool
    {
        return match ($this) {
            self::PENDING => in_array($status, [self::ACTIVE, self::INACTIVE, self::SUSPENDED, self::CLOSED], true),
            self::ACTIVE => in_array($status, [self::INACTIVE, self::SUSPENDED, self::CLOSED], true),
            self::INACTIVE => in_array($status, [self::ACTIVE, self::SUSPENDED, self::CLOSED], true),
            self::SUSPENDED => in_array($status, [self::ACTIVE, self::INACTIVE, self::CLOSED], true),
            self::CLOSED => false,
        };
    }
}
