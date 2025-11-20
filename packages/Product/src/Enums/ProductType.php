<?php

declare(strict_types=1);

namespace Nexus\Product\Enums;

use Nexus\Product\Exceptions\InvalidProductDataException;

/**
 * Product Type Enum
 *
 * Defines the fundamental nature of a product for accounting and inventory purposes.
 */
enum ProductType: string
{
    /**
     * Physical goods with inventory tracking
     * Stock levels are monitored, cost of goods sold applies
     */
    case STORABLE = 'storable';

    /**
     * Items consumed without stock tracking
     * Expensed immediately upon purchase (office supplies, maintenance materials)
     */
    case CONSUMABLE = 'consumable';

    /**
     * Intangible services
     * No inventory tracking, no physical dimensions
     */
    case SERVICE = 'service';

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
            'storable' => self::STORABLE,
            'consumable' => self::CONSUMABLE,
            'service' => self::SERVICE,
            default => throw InvalidProductDataException::invalidProductType($value),
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
            self::STORABLE => 'Storable Product',
            self::CONSUMABLE => 'Consumable Product',
            self::SERVICE => 'Service',
        };
    }

    /**
     * Check if product requires inventory tracking
     *
     * @return bool
     */
    public function requiresInventoryTracking(): bool
    {
        return match ($this) {
            self::STORABLE => true,
            self::CONSUMABLE, self::SERVICE => false,
        };
    }

    /**
     * Check if product can have physical dimensions
     *
     * @return bool
     */
    public function canHaveDimensions(): bool
    {
        return match ($this) {
            self::STORABLE, self::CONSUMABLE => true,
            self::SERVICE => false,
        };
    }

    /**
     * Check if product is a service
     *
     * @return bool
     */
    public function isService(): bool
    {
        return $this === self::SERVICE;
    }
}
