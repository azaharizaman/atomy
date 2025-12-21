<?php

declare(strict_types=1);

namespace Nexus\PaymentRails\Enums;

/**
 * Type of virtual card based on usage patterns.
 */
enum VirtualCardType: string
{
    /**
     * Single-use card - can only be used once.
     */
    case SINGLE_USE = 'single_use';

    /**
     * Multi-use card - can be used multiple times until limit or expiry.
     */
    case MULTI_USE = 'multi_use';

    /**
     * Supplier-locked card - can only be used with specific merchant.
     */
    case SUPPLIER_LOCKED = 'supplier_locked';

    /**
     * Subscription card - recurring payments to specific merchant.
     */
    case SUBSCRIPTION = 'subscription';

    /**
     * Get a human-readable label for the type.
     */
    public function label(): string
    {
        return match ($this) {
            self::SINGLE_USE => 'Single Use',
            self::MULTI_USE => 'Multi Use',
            self::SUPPLIER_LOCKED => 'Supplier Locked',
            self::SUBSCRIPTION => 'Subscription',
        };
    }

    /**
     * Check if this card type supports multiple transactions.
     */
    public function supportsMultipleTransactions(): bool
    {
        return match ($this) {
            self::MULTI_USE,
            self::SUPPLIER_LOCKED,
            self::SUBSCRIPTION => true,
            self::SINGLE_USE => false,
        };
    }

    /**
     * Check if this card type is merchant-restricted.
     */
    public function isMerchantRestricted(): bool
    {
        return match ($this) {
            self::SUPPLIER_LOCKED,
            self::SUBSCRIPTION => true,
            self::SINGLE_USE,
            self::MULTI_USE => false,
        };
    }

    /**
     * Get the typical use case description.
     */
    public function useCase(): string
    {
        return match ($this) {
            self::SINGLE_USE => 'One-time B2B payments, invoice payments',
            self::MULTI_USE => 'Recurring payments to various merchants',
            self::SUPPLIER_LOCKED => 'Payments to specific supplier only',
            self::SUBSCRIPTION => 'SaaS and recurring service payments',
        };
    }
}
