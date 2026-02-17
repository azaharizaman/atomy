<?php

declare(strict_types=1);

namespace Nexus\Period\Enums;

/**
 * Period Status Enum
 * 
 * Defines the lifecycle states of a fiscal period.
 * Lifecycle: Pending → Open → Closed → Locked
 */
enum PeriodStatus: string
{
    case Pending = 'pending';
    case Open = 'open';
    case Closed = 'closed';
    case Locked = 'locked';

    public function label(): string
    {
        return match($this) {
            self::Pending => 'Pending',
            self::Open => 'Open',
            self::Closed => 'Closed',
            self::Locked => 'Locked',
        };
    }

    public function canTransitionTo(self $newStatus): bool
    {
        return match($this) {
            self::Pending => in_array($newStatus, [self::Open], true),
            self::Open => in_array($newStatus, [self::Closed], true),
            self::Closed => in_array($newStatus, [self::Locked, self::Open], true), // Allow reopening or locking
            self::Locked => $newStatus === self::Closed, // Exceptional: Allow unlocking to Closed (requires special auth)
        };
    }

    public function isPostingAllowed(): bool
    {
        return $this === self::Open;
    }
}
