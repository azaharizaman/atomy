<?php

declare(strict_types=1);

namespace Nexus\Sales\Enums;

/**
 * Pricing strategy enum.
 * 
 * Defines different pricing calculation methods.
 */
enum PricingStrategy: string
{
    case LIST_PRICE = 'list_price';              // Standard catalog price
    case TIERED_DISCOUNT = 'tiered_discount';     // Volume-based quantity tiers
    case CUSTOMER_SPECIFIC = 'customer_specific'; // Contract price for specific customer
    case PROMOTIONAL = 'promotional';             // Time-limited promotional price

    /**
     * Check if strategy requires customer context.
     */
    public function requiresCustomer(): bool
    {
        return $this === self::CUSTOMER_SPECIFIC;
    }

    /**
     * Check if strategy is time-sensitive.
     */
    public function isTimeSensitive(): bool
    {
        return $this === self::PROMOTIONAL;
    }

    /**
     * Check if strategy uses quantity tiers.
     */
    public function usesQuantityTiers(): bool
    {
        return $this === self::TIERED_DISCOUNT;
    }
}
