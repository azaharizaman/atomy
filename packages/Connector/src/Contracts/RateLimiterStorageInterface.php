<?php

declare(strict_types=1);

namespace Nexus\Connector\Contracts;

use Nexus\Connector\ValueObjects\RateLimitConfig;

/**
 * Contract for rate limiter token bucket state persistence.
 *
 * This interface must be implemented by the application layer
 * using Redis, Memcached, or other shared storage mechanism.
 * 
 * CRITICAL: Token bucket state MUST be shared across all workers/processes
 * to enforce rate limits globally, not per-worker.
 */
interface RateLimiterStorageInterface
{
    /**
     * Get current token count for a service.
     *
     * @param string $serviceName Service identifier
     * @param RateLimitConfig $config Rate limit configuration
     * @return float Current token count
     */
    public function getTokens(string $serviceName, RateLimitConfig $config): float;

    /**
     * Consume tokens for a service.
     *
     * @param string $serviceName Service identifier
     * @param RateLimitConfig $config Rate limit configuration
     * @param float $tokens Number of tokens to consume
     * @return bool True if tokens were successfully consumed
     */
    public function consumeTokens(string $serviceName, RateLimitConfig $config, float $tokens): bool;

    /**
     * Refill tokens for a service based on elapsed time.
     *
     * @param string $serviceName Service identifier
     * @param RateLimitConfig $config Rate limit configuration
     * @return float Updated token count
     */
    public function refillTokens(string $serviceName, RateLimitConfig $config): float;

    /**
     * Reset rate limiter state for a service.
     *
     * @param string $serviceName Service identifier
     */
    public function reset(string $serviceName): void;

    /**
     * Get last refill timestamp for a service.
     *
     * @param string $serviceName Service identifier
     * @return float|null Unix timestamp or null if not set
     */
    public function getLastRefillTime(string $serviceName): ?float;
}
