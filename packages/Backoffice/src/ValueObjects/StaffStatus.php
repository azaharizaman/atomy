<?php

declare(strict_types=1);

namespace Nexus\Backoffice\ValueObjects;

enum StaffStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case ON_LEAVE = 'on_leave';
    case TERMINATED = 'terminated';

    public function isActive(): bool
    {
        return match ($this) {
            self::ACTIVE, self::ON_LEAVE => true,
            self::INACTIVE, self::TERMINATED => false,
        };
    }

    public function isTerminated(): bool
    {
        return $this === self::TERMINATED;
    }

    public function canHaveAssignments(): bool
    {
        return $this !== self::TERMINATED;
    }
}
