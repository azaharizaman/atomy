<?php

declare(strict_types=1);

namespace Nexus\Monitoring\Contracts;

/**
 * Cache Repository Interface
 *
 * Contract for cache operations used by monitoring components.
 * This is a minimal interface to avoid tight coupling to specific cache implementations.
 *
 * @package Nexus\Monitoring\Contracts
 */
interface CacheRepositoryInterface
{
    /**
     * Store an item in the cache.
     *
     * @param string $key Cache key
     * @param mixed $value Value to store
     * @param int $ttl Time to live in seconds
     * @return bool True if stored successfully
     */
    public function put(string $key, mixed $value, int $ttl): bool;

    /**
     * Retrieve an item from the cache.
     *
     * @param string $key Cache key
     * @param mixed $default Default value if key not found
     * @return mixed The cached value or default
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Remove an item from the cache.
     *
     * @param string $key Cache key
     * @return bool True if removed successfully
     */
    public function forget(string $key): bool;

    /**
     * Store an item in the cache, retrieving it if it already exists.
     *
     * @param string $key Cache key
     * @param int $ttl Time to live in seconds
     * @param callable $callback Callback to generate value if not cached
     * @return mixed The cached or generated value
     */
    public function remember(string $key, int $ttl, callable $callback): mixed;
}
