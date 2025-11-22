<?php

declare(strict_types=1);

namespace Nexus\Finance\Contracts;

/**
 * Cache Interface for Finance Package
 * 
 * Framework-agnostic caching contract.
 * Implementation provided by application layer (Redis, Memcached, etc.)
 */
interface CacheInterface
{
    /**
     * Retrieve an item from the cache
     * 
     * @param string $key Cache key
     * @param mixed $default Default value if key doesn't exist
     * @return mixed Cached value or default
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Store an item in the cache
     * 
     * @param string $key Cache key
     * @param mixed $value Value to cache
     * @param int $ttl Time to live in seconds
     * @return bool True if stored successfully
     */
    public function put(string $key, mixed $value, int $ttl): bool;

    /**
     * Retrieve an item from cache or execute callback and cache result
     * 
     * @param string $key Cache key
     * @param int $ttl Time to live in seconds
     * @param callable $callback Callback to execute if key doesn't exist
     * @return mixed Cached value or callback result
     */
    public function remember(string $key, int $ttl, callable $callback): mixed;

    /**
     * Remove an item from the cache
     * 
     * @param string $key Cache key
     * @return bool True if removed successfully
     */
    public function forget(string $key): bool;

    /**
     * Remove multiple items by pattern
     * 
     * @param string $pattern Key pattern (e.g., 'finance:accounts:*')
     * @return int Number of keys removed
     */
    public function forgetByPattern(string $pattern): int;
}
