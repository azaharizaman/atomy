<?php

declare(strict_types=1);

namespace Nexus\JournalEntry\Contracts;

use Nexus\Common\ValueObjects\Money;

/**
 * Currency Converter Interface
 *
 * Defines the contract for converting money between currencies.
 * This is typically implemented by adapters using the Currency package's ExchangeRateService.
 *
 * @package Nexus\JournalEntry\Contracts
 */
interface CurrencyConverterInterface
{
    /**
     * Convert money from one currency to another.
     *
     * @param Money $money The amount to convert
     * @param string $toCurrency Target currency code (ISO 4217)
     * @param \DateTimeImmutable|null $asOf Optional date for historical rate (defaults to current date)
     * @return Money Converted amount in target currency
     * @throws \Nexus\JournalEntry\Exceptions\InvalidExchangeRateException If exchange rate not found or invalid
     */
    public function convert(Money $money, string $toCurrency, ?\DateTimeImmutable $asOf = null): Money;

    /**
     * Check if conversion is supported between two currencies.
     *
     * @param string $fromCurrency Source currency code
     * @param string $toCurrency Target currency code
     * @return bool True if conversion is supported
     */
    public function supportsConversion(string $fromCurrency, string $toCurrency): bool;
}
