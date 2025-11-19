<?php

declare(strict_types=1);

namespace Nexus\Currency\Contracts;

use DateTimeImmutable;
use Nexus\Currency\ValueObjects\CurrencyPair;
use Nexus\Finance\ValueObjects\ExchangeRate;

/**
 * Rate Storage Interface
 *
 * Defines the contract for caching exchange rates to reduce API calls.
 * Implementations can use Redis, database, or any other storage mechanism.
 *
 * @package Nexus\Currency\Contracts
 */
interface RateStorageInterface
{
    /**
     * Get a cached exchange rate.
     *
     * @param CurrencyPair $pair The currency pair
     * @param DateTimeImmutable|null $asOf Optional date for historical rates
     * @return ExchangeRate|null The cached rate if found, null otherwise
     */
    public function get(CurrencyPair $pair, ?DateTimeImmutable $asOf = null): ?ExchangeRate;

    /**
     * Store an exchange rate in cache.
     *
     * @param CurrencyPair $pair The currency pair
     * @param ExchangeRate $rate The exchange rate to cache
     * @param int $ttl Time-to-live in seconds (0 = forever)
     * @return bool True if stored successfully
     */
    public function put(CurrencyPair $pair, ExchangeRate $rate, int $ttl = 3600): bool;

    /**
     * Remove a cached exchange rate.
     *
     * @param CurrencyPair $pair The currency pair
     * @param DateTimeImmutable|null $asOf Optional date for historical rates
     * @return bool True if removed successfully
     */
    public function forget(CurrencyPair $pair, ?DateTimeImmutable $asOf = null): bool;

    /**
     * Clear all cached exchange rates.
     *
     * @return bool True if cleared successfully
     */
    public function flush(): bool;

    /**
     * Check if a rate exists in cache.
     *
     * @param CurrencyPair $pair The currency pair
     * @param DateTimeImmutable|null $asOf Optional date for historical rates
     * @return bool True if cached rate exists
     */
    public function has(CurrencyPair $pair, ?DateTimeImmutable $asOf = null): bool;
}
