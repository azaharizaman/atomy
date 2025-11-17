<?php

declare(strict_types=1);

namespace Nexus\Backoffice\ValueObjects;

enum OfficeStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case TEMPORARY = 'temporary';
    case CLOSED = 'closed';

    public function isActive(): bool
    {
        return match ($this) {
            self::ACTIVE, self::TEMPORARY => true,
            self::INACTIVE, self::CLOSED => false,
        };
    }
}
