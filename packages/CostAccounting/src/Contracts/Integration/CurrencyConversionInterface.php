<?php

declare(strict_types=1);

namespace Nexus\CostAccounting\Contracts\Integration;

use DateTimeImmutable;
use Nexus\Common\ValueObjects\Money;
use Nexus\CostAccounting\ValueObjects\CostAmount;

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
     * @param CostAmount|Money $amount Amount to convert
     * @param string $fromCurrency Source currency code
     * @param string $toCurrency Target currency code
     * @param DateTimeImmutable|null $date Conversion date
     * @return CostAmount|Money
     */
    public function convert(
        CostAmount|Money $amount,
        string $fromCurrency,
        string $toCurrency,
        ?DateTimeImmutable $date = null
    ): CostAmount|Money;

    /**
     * Get exchange rate
     * 
     * @param string $fromCurrency Source currency code
     * @param string $toCurrency Target currency code
     * @param DateTimeImmutable|null $date Rate date
     * @return Money
     */
    public function getExchangeRate(
        string $fromCurrency,
        string $toCurrency,
        ?DateTimeImmutable $date = null
    ): Money;

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
