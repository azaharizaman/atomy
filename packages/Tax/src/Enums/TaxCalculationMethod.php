<?php

declare(strict_types=1);

namespace Nexus\Tax\Enums;

/**
 * Tax Calculation Method: How tax should be calculated
 * 
 * Defines the calculation approach for the transaction.
 * Affects whether tax is collected, deferred, or inclusive.
 */
enum TaxCalculationMethod: string
{
    case Standard = 'standard'; // Normal tax calculation (tax added to base)
    case ReverseCharge = 'reverse_charge'; // Tax liability deferred to buyer (EU VAT cross-border B2B)
    case Inclusive = 'inclusive'; // Tax already included in price
    case Exclusive = 'exclusive'; // Tax explicitly added (same as Standard, but clearer intent)

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match ($this) {
            self::Standard => 'Standard (Tax Added)',
            self::ReverseCharge => 'Reverse Charge (Buyer Self-Assesses)',
            self::Inclusive => 'Tax Inclusive (Tax in Price)',
            self::Exclusive => 'Tax Exclusive (Tax Added to Price)',
        };
    }

    /**
     * Check if tax amount should be collected from customer
     */
    public function collectsTax(): bool
    {
        return match ($this) {
            self::Standard, self::Inclusive, self::Exclusive => true,
            self::ReverseCharge => false, // Buyer self-assesses
        };
    }

    /**
     * Check if this method requires buyer to self-assess tax
     */
    public function requiresBuyerSelfAssessment(): bool
    {
        return match ($this) {
            self::ReverseCharge => true,
            default => false,
        };
    }

    /**
     * Check if tax is included in the displayed price
     */
    public function isTaxInPrice(): bool
    {
        return match ($this) {
            self::Inclusive => true,
            default => false,
        };
    }

    /**
     * Calculate gross amount from net amount and tax
     */
    public function calculateGross(string $netAmount, string $taxAmount): string
    {
        return match ($this) {
            self::Standard, self::Exclusive => bcadd($netAmount, $taxAmount, 4),
            self::Inclusive => $netAmount, // Tax already in net
            self::ReverseCharge => $netAmount, // No tax collected
        };
    }

    /**
     * Calculate net amount from gross amount and tax
     */
    public function calculateNet(string $grossAmount, string $taxAmount): string
    {
        return match ($this) {
            self::Standard, self::Exclusive => bcsub($grossAmount, $taxAmount, 4),
            self::Inclusive => $grossAmount, // Gross = net when inclusive
            self::ReverseCharge => $grossAmount, // No tax collected
        };
    }
}
