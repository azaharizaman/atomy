<?php

declare(strict_types=1);

namespace Nexus\Inventory\Enums;

/**
 * Serial number status types
 */
enum SerialStatus: string
{
    case AVAILABLE = 'available';
    case RESERVED = 'reserved';
    case SOLD = 'sold';
    case RETURNED = 'returned';
    case DAMAGED = 'damaged';
    case SCRAPPED = 'scrapped';

    /**
     * Check if serial is available for sale
     */
    public function isAvailable(): bool
    {
        return $this === self::AVAILABLE;
    }

    /**
     * Check if serial is reserved
     */
    public function isReserved(): bool
    {
        return $this === self::RESERVED;
    }

    /**
     * Check if serial has been sold
     */
    public function isSold(): bool
    {
        return $this === self::SOLD;
    }

    /**
     * Check if serial is in a usable state (available or reserved)
     */
    public function isUsable(): bool
    {
        return in_array($this, [self::AVAILABLE, self::RESERVED], true);
    }
}
