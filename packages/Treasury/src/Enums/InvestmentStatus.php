<?php

declare(strict_types=1);

namespace Nexus\Treasury\Enums;

/**
 * Investment status
 */
enum InvestmentStatus: string
{
    case ACTIVE = 'active';
    case MATURED = 'matured';
    case CANCELLED = 'cancelled';
    case ROLLED_OVER = 'rolled_over';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::MATURED => 'Matured',
            self::CANCELLED => 'Cancelled',
            self::ROLLED_OVER => 'Rolled Over',
        };
    }

    public function isActive(): bool
    {
        return $this === self::ACTIVE;
    }
}
