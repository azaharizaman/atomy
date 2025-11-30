<?php

declare(strict_types=1);

namespace Nexus\Currency\Contracts;

use Nexus\Currency\ValueObjects\Currency;

/**
 * Currency Manager Interface
 *
 * Defines the contract for high-level currency management operations.
 *
 * @package Nexus\Currency\Contracts
 */
interface CurrencyManagerInterface
{
    /**
     * Get a currency by its code.
     *
     * @param string $code ISO 4217 currency code
     * @return Currency
     * @throws \Nexus\Currency\Exceptions\CurrencyNotFoundException If currency not found
     */
    public function getCurrency(string $code): Currency;

    /**
     * Validate a currency code.
     *
     * @param string $code ISO 4217 currency code
     * @return void
     * @throws \Nexus\Currency\Exceptions\InvalidCurrencyCodeException If code is invalid
     */
    public function validateCode(string $code): void;

    /**
     * Get the decimal precision for a currency.
     *
     * @param string $code ISO 4217 currency code
     * @return int Number of decimal places
     * @throws \Nexus\Currency\Exceptions\CurrencyNotFoundException If currency not found
     */
    public function getDecimalPrecision(string $code): int;

    /**
     * Format an amount according to currency rules.
     *
     * @param string $amount Amount as BCMath string
     * @param string $currencyCode ISO 4217 currency code
     * @param bool $includeSymbol Whether to include currency symbol
     * @param bool $includeCode Whether to include currency code
     * @return string Formatted amount
     * @throws \Nexus\Currency\Exceptions\CurrencyNotFoundException If currency not found
     */
    public function formatAmount(
        string $amount,
        string $currencyCode,
        bool $includeSymbol = true,
        bool $includeCode = false
    ): string;

    /**
     * Check if a currency code is valid and exists.
     *
     * @param string $code ISO 4217 currency code
     * @return bool True if valid and exists
     */
    public function exists(string $code): bool;

    /**
     * Get all available currencies.
     *
     * @return array<string, Currency> Array of currencies indexed by code
     */
    public function getAllCurrencies(): array;
}
