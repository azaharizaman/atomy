<?php

declare(strict_types=1);

namespace Nexus\Identity\Contracts;

/**
 * Repository contract for cache operations with TTL support.
 *
 * Used for rate limiting, OTP storage, and device trust caching.
 */
interface CacheRepositoryInterface
{
    /**
     * Get a value from cache.
     *
     * @param string $key The cache key
     * @param mixed $default Default value if key doesn't exist
     * @return mixed The cached value or default
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Store a value in cache with TTL.
     *
     * @param string $key The cache key
     * @param mixed $value The value to store
     * @param int $ttl Time-to-live in seconds
     * @return bool True if stored successfully
     */
    public function put(string $key, mixed $value, int $ttl): bool;

    /**
     * Store a value in cache with remember pattern.
     *
     * Returns cached value if exists, otherwise executes callback and caches result.
     *
     * @param string $key The cache key
     * @param int $ttl Time-to-live in seconds
     * @param callable $callback Callback to execute if cache miss
     * @return mixed The cached or computed value
     */
    public function remember(string $key, int $ttl, callable $callback): mixed;

    /**
     * Remove a value from cache.
     *
     * @param string $key The cache key
     * @return bool True if removed
     */
    public function forget(string $key): bool;

    /**
     * Increment a numeric value in cache.
     *
     * Used for rate limiting counters.
     *
     * @param string $key The cache key
     * @param int $value The increment amount
     * @return int The new value
     */
    public function increment(string $key, int $value = 1): int;

    /**
     * Decrement a numeric value in cache.
     *
     * @param string $key The cache key
     * @param int $value The decrement amount
     * @return int The new value
     */
    public function decrement(string $key, int $value = 1): int;

    /**
     * Check if a key exists in cache.
     *
     * @param string $key The cache key
     * @return bool True if key exists
     */
    public function has(string $key): bool;

    /**
     * Add a value to cache only if it doesn't exist.
     *
     * @param string $key The cache key
     * @param mixed $value The value to store
     * @param int $ttl Time-to-live in seconds
     * @return bool True if added, false if key already exists
     */
    public function add(string $key, mixed $value, int $ttl): bool;

    /**
     * Get multiple values from cache.
     *
     * @param array<string> $keys Array of cache keys
     * @return array<string, mixed> Array of key-value pairs
     */
    public function many(array $keys): array;

    /**
     * Store multiple values in cache.
     *
     * @param array<string, mixed> $values Array of key-value pairs
     * @param int $ttl Time-to-live in seconds
     * @return bool True if all stored successfully
     */
    public function putMany(array $values, int $ttl): bool;

    /**
     * Remove multiple values from cache.
     *
     * @param array<string> $keys Array of cache keys
     * @return bool True if all removed
     */
    public function forgetMany(array $keys): bool;

    /**
     * Clear all cached values.
     *
     * Use with caution in multi-tenant environments.
     *
     * @return bool True if cleared
     */
    public function flush(): bool;

    /**
     * Get the TTL (time-to-live) remaining for a cache key.
     *
     * @param string $key The cache key
     * @return int Seconds remaining until expiration, 0 if expired or not found
     */
    public function getTtl(string $key): int;
}
