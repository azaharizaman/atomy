<?php

declare(strict_types=1);

namespace Nexus\Messaging\Contracts;

/**
 * Rate limiter contract for high-volume throttling
 * 
 * L3.1: Application layer implements using Redis, database, or in-memory cache
 * 
 * @package Nexus\Messaging
 */
interface RateLimiterInterface
{
    /**
     * Check if action is allowed within rate limit
     * 
     * @param string $key Unique key (e.g., "tenant:{tenantId}:channel:{channel}")
     * @param int $maxAttempts Maximum attempts allowed
     * @param int $decaySeconds Time window in seconds
     * @return bool True if action is allowed
     */
    public function allowAction(string $key, int $maxAttempts, int $decaySeconds): bool;

    /**
     * Get remaining attempts
     * 
     * @param string $key
     * @param int $maxAttempts
     * @return int
     */
    public function remainingAttempts(string $key, int $maxAttempts): int;

    /**
     * Get time until next attempt is available
     * 
     * @param string $key
     * @return int Seconds until reset
     */
    public function availableIn(string $key): int;

    /**
     * Clear rate limit for key
     * 
     * @param string $key
     * @return void
     */
    public function clear(string $key): void;
}
