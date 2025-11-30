<?php

declare(strict_types=1);

namespace Nexus\Currency\Contracts;

use DateTimeImmutable;
use Nexus\Currency\ValueObjects\CurrencyPair;
use Nexus\Finance\ValueObjects\ExchangeRate;

/**
 * Exchange Rate Provider Interface
 *
 * Defines the contract for fetching exchange rates from external sources.
 * Implementations can connect to APIs like ECB, Fixer.io, Open Exchange Rates, etc.
 *
 * @package Nexus\Currency\Contracts
 */
interface ExchangeRateProviderInterface
{
    /**
     * Get the exchange rate for a currency pair.
     *
     * @param CurrencyPair $pair The currency pair (e.g., USD/EUR)
     * @param DateTimeImmutable|null $asOf Optional date for historical rates (null = current)
     * @return ExchangeRate The exchange rate
     * @throws \Nexus\Currency\Exceptions\ExchangeRateNotFoundException If rate not found
     * @throws \Nexus\Currency\Exceptions\ExchangeRateProviderException If provider fails
     */
    public function getRate(CurrencyPair $pair, ?DateTimeImmutable $asOf = null): ExchangeRate;

    /**
     * Get exchange rates for multiple currency pairs.
     *
     * @param array<CurrencyPair> $pairs Array of currency pairs
     * @param DateTimeImmutable|null $asOf Optional date for historical rates
     * @return array<string, ExchangeRate> Array of exchange rates indexed by pair string (e.g., "USD/EUR")
     * @throws \Nexus\Currency\Exceptions\ExchangeRateProviderException If provider fails
     */
    public function getRates(array $pairs, ?DateTimeImmutable $asOf = null): array;

    /**
     * Check if the provider supports historical rates.
     *
     * @return bool True if historical rates are supported
     */
    public function supportsHistoricalRates(): bool;

    /**
     * Get the name of the provider (for logging/debugging).
     *
     * @return string Provider name (e.g., "ECB", "Fixer.io")
     */
    public function getProviderName(): string;

    /**
     * Check if the provider is available/healthy.
     *
     * @return bool True if provider is available
     */
    public function isAvailable(): bool;
}
