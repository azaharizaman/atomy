<?php

declare(strict_types=1);

namespace Nexus\Product\Enums;

use Nexus\Product\Exceptions\InvalidProductDataException;

/**
 * Tracking Method Enum
 *
 * Defines how inventory movement is tracked for a product.
 * Used by Nexus\Inventory package to determine granularity of stock records.
 */
enum TrackingMethod: string
{
    /**
     * No tracking beyond quantity
     * Standard inventory management with total quantity only
     */
    case NONE = 'none';

    /**
     * Batch/Lot number tracking
     * Groups of items with same production date, expiry, etc.
     * Used for food, pharmaceuticals, chemicals
     */
    case LOT_NUMBER = 'lot_number';

    /**
     * Unique serial number tracking
     * Individual item identification (electronics, machinery, vehicles)
     * Each unit has unique identifier for warranty, maintenance tracking
     */
    case SERIAL_NUMBER = 'serial_number';

    /**
     * Create from string value
     *
     * @param string $value
     * @return self
     * @throws InvalidProductDataException
     */
    public static function fromString(string $value): self
    {
        return match (strtolower($value)) {
            'none' => self::NONE,
            'lot_number', 'lot' => self::LOT_NUMBER,
            'serial_number', 'serial' => self::SERIAL_NUMBER,
            default => throw InvalidProductDataException::invalidTrackingMethod($value),
        };
    }

    /**
     * Get human-readable label
     *
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::NONE => 'No Tracking',
            self::LOT_NUMBER => 'Lot/Batch Number',
            self::SERIAL_NUMBER => 'Serial Number',
        };
    }

    /**
     * Check if tracking requires unique identifiers
     *
     * @return bool
     */
    public function requiresUniqueIdentifier(): bool
    {
        return match ($this) {
            self::LOT_NUMBER, self::SERIAL_NUMBER => true,
            self::NONE => false,
        };
    }

    /**
     * Check if tracking is at individual unit level
     *
     * @return bool
     */
    public function isUnitLevel(): bool
    {
        return $this === self::SERIAL_NUMBER;
    }

    /**
     * Check if tracking is at batch level
     *
     * @return bool
     */
    public function isBatchLevel(): bool
    {
        return $this === self::LOT_NUMBER;
    }
}
