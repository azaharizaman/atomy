<?php

declare(strict_types=1);

namespace Nexus\CostAccounting\Contracts\Integration;

/**
 * Currency Conversion Interface
 * 
 * Integration contract for Nexus\Currency package.
 * Provides multi-currency support for cost calculations.
 */
interface CurrencyConversionInterface
{
    /**
     * Convert amount between currencies
     * 
     * @param float $amount Amount to convert
     * @param string $fromCurrency Source currency code
     * @param string $toCurrency Target currency code
     * @param string|null $date Conversion date
     * @return float
     */
    public function convert(
        float $amount,
        string $fromCurrency,
        string $toCurrency,
        ?string $date = null
    ): float;

    /**
     * Get exchange rate
     * 
     * @param string $fromCurrency Source currency code
     * @param string $toCurrency Target currency code
     * @param string|null $date Rate date
     * @return float
     */
    public function getExchangeRate(
        string $fromCurrency,
        string $toCurrency,
        ?string $date = null
    ): float;

    /**
     * Validate currency
     * 
     * @param string $currencyCode Currency code
     * @return bool
     */
    public function validateCurrency(string $currencyCode): bool;

    /**
     * Get currency precision
     * 
     * @param string $currencyCode Currency code
     * @return int
     */
    public function getCurrencyPrecision(string $currencyCode): int;

    /**
     * Format amount with currency
     * 
     * @param float $amount Amount to format
     * @param string $currencyCode Currency code
     * @return string
     */
    public function formatAmount(
        float $amount,
        string $currencyCode
    ): string;
}
