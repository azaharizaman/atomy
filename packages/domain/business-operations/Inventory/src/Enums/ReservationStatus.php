<?php

declare(strict_types=1);

namespace Nexus\Inventory\Enums;

/**
 * Reservation status types
 */
enum ReservationStatus: string
{
    case ACTIVE = 'active';
    case FULFILLED = 'fulfilled';
    case CANCELLED = 'cancelled';
    case EXPIRED = 'expired';

    /**
     * Check if reservation is active
     */
    public function isActive(): bool
    {
        return $this === self::ACTIVE;
    }

    /**
     * Check if reservation has been fulfilled
     */
    public function isFulfilled(): bool
    {
        return $this === self::FULFILLED;
    }

    /**
     * Check if reservation was cancelled
     */
    public function isCancelled(): bool
    {
        return $this === self::CANCELLED;
    }

    /**
     * Check if reservation is in a terminal state
     */
    public function isTerminal(): bool
    {
        return in_array($this, [self::FULFILLED, self::CANCELLED, self::EXPIRED], true);
    }
}
