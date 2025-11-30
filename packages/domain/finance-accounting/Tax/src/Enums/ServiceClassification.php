<?php

declare(strict_types=1);

namespace Nexus\Tax\Enums;

/**
 * Service Classification: Type of service for place-of-supply rules
 * 
 * Categorizes services to determine tax jurisdiction for cross-border transactions.
 * Critical for EU VAT, UK VAT, and GST cross-border logic.
 */
enum ServiceClassification: string
{
    case DigitalService = 'digital_service'; // SaaS, downloads, streaming
    case TelecomService = 'telecom_service'; // Telecommunications
    case ConsultingService = 'consulting_service'; // Professional services
    case PhysicalGoods = 'physical_goods'; // Tangible goods
    case Other = 'other'; // Unclassified

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match ($this) {
            self::DigitalService => 'Digital Service',
            self::TelecomService => 'Telecommunications Service',
            self::ConsultingService => 'Consulting/Professional Service',
            self::PhysicalGoods => 'Physical Goods',
            self::Other => 'Other/Unclassified',
        };
    }

    /**
     * Check if place-of-supply logic is required for cross-border transactions
     * 
     * For digital/telecom services: EU VAT taxed at customer location (B2C) or supplier location (B2B)
     */
    public function requiresPlaceOfSupplyLogic(): bool
    {
        return match ($this) {
            self::DigitalService, self::TelecomService => true,
            default => false,
        };
    }

    /**
     * Get tax jurisdiction rule for B2C cross-border transactions
     */
    public function getB2CJurisdictionRule(): string
    {
        return match ($this) {
            self::DigitalService, self::TelecomService => 'customer_location',
            self::PhysicalGoods => 'destination',
            self::ConsultingService => 'performance_location',
            self::Other => 'destination',
        };
    }

    /**
     * Get tax jurisdiction rule for B2B cross-border transactions
     */
    public function getB2BJurisdictionRule(): string
    {
        return match ($this) {
            self::DigitalService, self::TelecomService => 'reverse_charge', // EU VAT B2B
            self::PhysicalGoods => 'destination',
            self::ConsultingService => 'customer_location',
            self::Other => 'destination',
        };
    }

    /**
     * Check if this service type requires VAT MOSS/OSS registration
     * (EU Mini One-Stop Shop for digital services)
     */
    public function requiresMOSSRegistration(): bool
    {
        return match ($this) {
            self::DigitalService, self::TelecomService => true,
            default => false,
        };
    }

    /**
     * Get typical tax treatment for this service type
     */
    public function getTypicalTaxTreatment(): string
    {
        return match ($this) {
            self::DigitalService => 'Standard rate VAT/GST at customer location',
            self::TelecomService => 'Standard rate VAT/GST at customer location',
            self::ConsultingService => 'Standard rate, may qualify for reverse charge (B2B)',
            self::PhysicalGoods => 'Standard rate at destination',
            self::Other => 'Varies by jurisdiction',
        };
    }
}
