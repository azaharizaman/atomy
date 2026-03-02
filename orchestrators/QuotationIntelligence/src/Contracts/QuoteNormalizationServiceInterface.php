<?php

declare(strict_types=1);

namespace Nexus\QuotationIntelligence\Contracts;

/**
 * Interface for normalization of UoM and currency in quotes.
 */
interface QuoteNormalizationServiceInterface
{
    /**
     * Normalize a quoted quantity/unit to the RFQ's base unit.
     * 
     * @param float $quantity The vendor's original quantity
     * @param string $fromUnit The vendor's original UoM (e.g., 'Box of 12')
     * @param string $toUnit The target RFQ base unit (e.g., 'Each')
     * 
     * @return float The normalized quantity in the target unit
     * 
     * @throws \Nexus\QuotationIntelligence\Exceptions\UomNormalizationException If conversion not possible
     */
    public function normalizeQuantity(float $quantity, string $fromUnit, string $toUnit): float;

    /**
     * Normalize a unit price to a base currency and base unit.
     * 
     * @param float $unitPrice Original price in original currency/unit
     * @param string $fromCurrency Original currency code (e.g., 'EUR')
     * @param string $toCurrency Target currency code (e.g., 'USD')
     * @param \DateTimeImmutable|null $lockDate If provided, use FX rate at this date
     * 
     * @return float The normalized price
     */
    public function normalizePrice(
        float $unitPrice,
        string $fromCurrency,
        string $toCurrency,
        ?\DateTimeImmutable $lockDate = null
    ): float;
}
