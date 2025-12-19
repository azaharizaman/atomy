<?php

declare(strict_types=1);

namespace Nexus\Payment\Contracts;

use DateTimeImmutable;
use Nexus\Common\ValueObjects\Money;
use Nexus\Payment\ValueObjects\ExchangeRateSnapshot;

/**
 * Currency Conversion Interface
 *
 * This is a HOOK interface for currency conversion operations.
 * The actual implementation should delegate to Nexus\Currency package.
 *
 * This interface allows the Payment package to remain decoupled from
 * the Currency package while still supporting multi-currency payments.
 *
 * Consuming applications must implement this interface using Nexus\Currency.
 *
 * @example
 * ```php
 * // In consuming application (adapter layer):
 * use Nexus\Currency\Contracts\ExchangeRateProviderInterface;
 * use Nexus\Payment\Contracts\CurrencyConversionInterface;
 *
 * final readonly class CurrencyConversionAdapter implements CurrencyConversionInterface
 * {
 *     public function __construct(
 *         private ExchangeRateProviderInterface $rateProvider
 *     ) {}
 *
 *     public function convert(Money $amount, string $targetCurrency, ?DateTimeImmutable $asOf = null): Money
 *     {
 *         $rate = $this->rateProvider->getRate($amount->getCurrency(), $targetCurrency, $asOf);
 *         return Money::of(
 *             bcmul($amount->getAmountAsString(), $rate, 2),
 *             $targetCurrency
 *         );
 *     }
 * }
 * ```
 *
 * @package Nexus\Payment\Contracts
 */
interface CurrencyConversionInterface
{
    /**
     * Convert an amount to a different currency.
     *
     * @param Money $amount The amount to convert
     * @param string $targetCurrency Target currency code (ISO 4217)
     * @param DateTimeImmutable|null $asOf Optional date for historical rate
     * @return Money Converted amount
     * @throws \Nexus\Payment\Exceptions\CurrencyConversionException If conversion fails
     */
    public function convert(
        Money $amount,
        string $targetCurrency,
        ?DateTimeImmutable $asOf = null
    ): Money;

    /**
     * Get the current exchange rate and capture it as a snapshot.
     *
     * @param string $sourceCurrency Source currency code (ISO 4217)
     * @param string $targetCurrency Target currency code (ISO 4217)
     * @param DateTimeImmutable|null $asOf Optional date for historical rate
     * @return ExchangeRateSnapshot Captured exchange rate
     * @throws \Nexus\Payment\Exceptions\CurrencyConversionException If rate unavailable
     */
    public function captureRate(
        string $sourceCurrency,
        string $targetCurrency,
        ?DateTimeImmutable $asOf = null
    ): ExchangeRateSnapshot;

    /**
     * Check if a currency pair is supported.
     *
     * @param string $sourceCurrency Source currency code (ISO 4217)
     * @param string $targetCurrency Target currency code (ISO 4217)
     * @return bool True if conversion is supported
     */
    public function isSupported(string $sourceCurrency, string $targetCurrency): bool;

    /**
     * Get the base currency for the tenant.
     *
     * @return string Base currency code (ISO 4217)
     */
    public function getBaseCurrency(): string;
}
