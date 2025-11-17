<?php

declare(strict_types=1);

namespace Nexus\Backoffice\ValueObjects;

enum UnitStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case COMPLETED = 'completed';
    case DISBANDED = 'disbanded';

    public function isActive(): bool
    {
        return $this === self::ACTIVE;
    }

    public function isEnded(): bool
    {
        return match ($this) {
            self::COMPLETED, self::DISBANDED => true,
            default => false,
        };
    }
}
