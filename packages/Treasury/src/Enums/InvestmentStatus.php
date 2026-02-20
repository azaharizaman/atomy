<?php

declare(strict_types=1);

namespace Nexus\Treasury\Enums;

enum InvestmentStatus: string
{
    case PENDING = 'pending';
    case ACTIVE = 'active';
    case MATURED = 'matured';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::ACTIVE => 'Active',
            self::MATURED => 'Matured',
            self::CANCELLED => 'Cancelled',
        };
    }

    public function isPending(): bool
    {
        return $this === self::PENDING;
    }

    public function isActive(): bool
    {
        return $this === self::ACTIVE;
    }

    public function isMatured(): bool
    {
        return $this === self::MATURED;
    }

    public function isCancelled(): bool
    {
        return $this === self::CANCELLED;
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::MATURED, self::CANCELLED], true);
    }

    public function canTransitionTo(self $status): bool
    {
        return match ($this) {
            self::PENDING => in_array($status, [self::ACTIVE, self::CANCELLED], true),
            self::ACTIVE => in_array($status, [self::MATURED, self::CANCELLED], true),
            self::MATURED, self::CANCELLED => false,
        };
    }
}
