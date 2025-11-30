<?php

declare(strict_types=1);

namespace Nexus\Inventory\Enums;

/**
 * Stock movement types
 */
enum MovementType: string
{
    case RECEIPT = 'receipt';
    case ISSUE = 'issue';
    case ADJUSTMENT = 'adjustment';
    case TRANSFER_OUT = 'transfer_out';
    case TRANSFER_IN = 'transfer_in';
    case RESERVATION = 'reservation';
    case RESERVATION_RELEASE = 'reservation_release';

    /**
     * Check if this is an inbound movement (increases stock)
     */
    public function isInbound(): bool
    {
        return in_array($this, [
            self::RECEIPT,
            self::TRANSFER_IN,
            self::RESERVATION_RELEASE,
        ], true);
    }

    /**
     * Check if this is an outbound movement (decreases stock)
     */
    public function isOutbound(): bool
    {
        return in_array($this, [
            self::ISSUE,
            self::TRANSFER_OUT,
            self::RESERVATION,
        ], true);
    }

    /**
     * Check if this is an adjustment movement
     */
    public function isAdjustment(): bool
    {
        return $this === self::ADJUSTMENT;
    }
}
