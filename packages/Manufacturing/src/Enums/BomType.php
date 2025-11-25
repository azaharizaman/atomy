<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Enums;

/**
 * BOM type enum.
 *
 * Defines the purpose/usage type of a Bill of Materials.
 */
enum BomType: string
{
    /**
     * Engineering BOM - product design and development.
     */
    case ENGINEERING = 'engineering';

    /**
     * Manufacturing BOM - production use with full process details.
     */
    case MANUFACTURING = 'manufacturing';

    /**
     * Planning BOM - used for MRP/MPS planning only.
     */
    case PLANNING = 'planning';

    /**
     * Service BOM - spare parts and service requirements.
     */
    case SERVICE = 'service';

    /**
     * Configurable BOM - with options and variants.
     */
    case CONFIGURABLE = 'configurable';

    /**
     * Phantom BOM - intermediate assembly not stocked.
     */
    case PHANTOM = 'phantom';

    /**
     * Check if BOM type is used in production.
     */
    public function isProductionType(): bool
    {
        return match ($this) {
            self::MANUFACTURING, self::PHANTOM => true,
            default => false,
        };
    }

    /**
     * Check if BOM type maintains inventory.
     */
    public function maintainsInventory(): bool
    {
        return match ($this) {
            self::PHANTOM => false,
            default => true,
        };
    }

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::ENGINEERING => 'Engineering BOM',
            self::MANUFACTURING => 'Manufacturing BOM',
            self::PLANNING => 'Planning BOM',
            self::SERVICE => 'Service BOM',
            self::CONFIGURABLE => 'Configurable BOM',
            self::PHANTOM => 'Phantom BOM',
        };
    }

    /**
     * Get description.
     */
    public function description(): string
    {
        return match ($this) {
            self::ENGINEERING => 'Used during product design and development',
            self::MANUFACTURING => 'Used for production with full process details',
            self::PLANNING => 'Used for MRP/MPS planning only',
            self::SERVICE => 'Used for spare parts and service requirements',
            self::CONFIGURABLE => 'Contains options and variants for configuration',
            self::PHANTOM => 'Intermediate assembly that is not stocked',
        };
    }
}
