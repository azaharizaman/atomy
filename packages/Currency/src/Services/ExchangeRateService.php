<?php

declare(strict_types=1);

namespace Nexus\Currency\Services;

use DateTimeImmutable;
use Nexus\Currency\Contracts\CurrencyManagerInterface;
use Nexus\Currency\Contracts\ExchangeRateProviderInterface;
use Nexus\Currency\Contracts\RateStorageInterface;
use Nexus\Currency\Exceptions\ExchangeRateNotFoundException;
use Nexus\Currency\Exceptions\ExchangeRateProviderException;
use Nexus\Currency\ValueObjects\CurrencyPair;
use Nexus\Finance\ValueObjects\ExchangeRate;
use Nexus\Finance\ValueObjects\Money;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Exchange Rate Service
 *
 * Provides exchange rate lookup, caching, and currency conversion operations.
 * Coordinates between the rate provider and storage layer.
 *
 * @package Nexus\Currency\Services
 */
class ExchangeRateService
{
    public function __construct(
        private readonly ExchangeRateProviderInterface $provider,
        private readonly RateStorageInterface $storage,
        private readonly CurrencyManagerInterface $currencyManager,
        private readonly LoggerInterface $logger = new NullLogger()
    ) {
    }

    /**
     * Get the exchange rate for a currency pair.
     *
     * Uses caching to reduce API calls. Falls back to provider if not cached.
     *
     * @param CurrencyPair $pair The currency pair
     * @param DateTimeImmutable|null $asOf Optional date for historical rates
     * @return ExchangeRate
     * @throws ExchangeRateNotFoundException
     * @throws ExchangeRateProviderException
     */
    public function getRate(CurrencyPair $pair, ?DateTimeImmutable $asOf = null): ExchangeRate
    {
        // Validate currencies exist
        $this->currencyManager->validateCode($pair->getFromCode());
        $this->currencyManager->validateCode($pair->getToCode());

        // Check cache first
        $cachedRate = $this->storage->get($pair, $asOf);
        if ($cachedRate !== null) {
            $this->logger->debug("Exchange rate cache hit for {$pair->toString()}");
            return $cachedRate;
        }

        // Fetch from provider
        $this->logger->info("Fetching exchange rate from provider for {$pair->toString()}", [
            'provider' => $this->provider->getProviderName(),
            'as_of' => $asOf?->format('Y-m-d'),
        ]);

        try {
            $rate = $this->provider->getRate($pair, $asOf);

            // Cache the rate (shorter TTL for current rates, longer for historical)
            $ttl = $asOf === null ? 3600 : 86400; // 1 hour for current, 24 hours for historical
            $this->storage->put($pair, $rate, $ttl);

            return $rate;
        } catch (\Throwable $e) {
            $this->logger->error("Failed to fetch exchange rate for {$pair->toString()}", [
                'error' => $e->getMessage(),
                'provider' => $this->provider->getProviderName(),
            ]);

            if ($e instanceof ExchangeRateNotFoundException || $e instanceof ExchangeRateProviderException) {
                throw $e;
            }

            throw ExchangeRateProviderException::apiFailure($this->provider->getProviderName(), $e);
        }
    }

    /**
     * Get exchange rates for multiple currency pairs.
     *
     * @param array<CurrencyPair> $pairs Array of currency pairs
     * @param DateTimeImmutable|null $asOf Optional date for historical rates
     * @return array<string, ExchangeRate> Rates indexed by pair string
     */
    public function getRates(array $pairs, ?DateTimeImmutable $asOf = null): array
    {
        $rates = [];
        $pairsToFetch = [];

        // Check cache for each pair
        foreach ($pairs as $pair) {
            $cachedRate = $this->storage->get($pair, $asOf);
            if ($cachedRate !== null) {
                $rates[$pair->toString()] = $cachedRate;
            } else {
                $pairsToFetch[] = $pair;
            }
        }

        // Fetch missing rates from provider
        if (count($pairsToFetch) > 0) {
            try {
                $fetchedRates = $this->provider->getRates($pairsToFetch, $asOf);

                // Cache and merge
                $ttl = $asOf === null ? 3600 : 86400;
                foreach ($fetchedRates as $pairString => $rate) {
                    $pair = CurrencyPair::fromString($pairString);
                    $this->storage->put($pair, $rate, $ttl);
                    $rates[$pairString] = $rate;
                }
            } catch (\Throwable $e) {
                $this->logger->error('Failed to fetch multiple exchange rates', [
                    'error' => $e->getMessage(),
                    'provider' => $this->provider->getProviderName(),
                ]);

                throw ExchangeRateProviderException::apiFailure($this->provider->getProviderName(), $e);
            }
        }

        return $rates;
    }

    /**
     * Convert money from one currency to another.
     *
     * @param Money $money The amount to convert
     * @param string $toCurrency Target currency code
     * @param DateTimeImmutable|null $asOf Optional date for historical rate
     * @return Money Converted amount
     * @throws ExchangeRateNotFoundException
     * @throws ExchangeRateProviderException
     */
    public function convert(Money $money, string $toCurrency, ?DateTimeImmutable $asOf = null): Money
    {
        // If same currency, return as-is
        if ($money->getCurrency() === $toCurrency) {
            return $money;
        }

        $pair = new CurrencyPair($money->getCurrency(), $toCurrency);
        $rate = $this->getRate($pair, $asOf);

        $convertedMoney = $rate->convert($money);

        $this->logger->info("Converted {$money} to {$convertedMoney}", [
            'rate' => $rate->getRate(),
            'as_of' => $asOf?->format('Y-m-d'),
        ]);

        return $convertedMoney;
    }

    /**
     * Refresh exchange rates for specific currency pairs.
     *
     * Forces a fetch from the provider, bypassing cache.
     *
     * @param array<CurrencyPair> $pairs Currency pairs to refresh
     * @return void
     */
    public function refreshRates(array $pairs): void
    {
        $this->logger->info('Refreshing exchange rates', [
            'pairs' => array_map(fn($p) => $p->toString(), $pairs),
        ]);

        foreach ($pairs as $pair) {
            // Clear cache
            $this->storage->forget($pair);

            try {
                // Fetch fresh rate
                $rate = $this->provider->getRate($pair);
                $this->storage->put($pair, $rate, 3600);
            } catch (\Throwable $e) {
                $this->logger->error("Failed to refresh rate for {$pair->toString()}", [
                    'error' => $e->getMessage(),
                ]);
                // Continue with other pairs
            }
        }
    }

    /**
     * Clear all cached exchange rates.
     */
    public function clearCache(): void
    {
        $this->logger->info('Clearing exchange rate cache');
        $this->storage->flush();
    }

    /**
     * Check if the exchange rate provider supports historical rates.
     */
    public function supportsHistoricalRates(): bool
    {
        return $this->provider->supportsHistoricalRates();
    }

    /**
     * Get the name of the active provider.
     */
    public function getProviderName(): string
    {
        return $this->provider->getProviderName();
    }

    /**
     * Check if the provider is currently available.
     */
    public function isProviderAvailable(): bool
    {
        return $this->provider->isAvailable();
    }
}
