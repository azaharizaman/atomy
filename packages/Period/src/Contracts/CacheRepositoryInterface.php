<?php

declare(strict_types=1);

namespace Nexus\Period\Contracts;

/**
 * Cache Repository Interface
 * 
 * Contract for caching operations needed by Period package.
 * Implementation must be provided in the application layer.
 */
interface CacheRepositoryInterface
{
    /**
     * Retrieve an item from the cache
     * 
     * @param string $key The cache key
     * @return mixed The cached value or null if not found
     */
    public function get(string $key): mixed;

    /**
     * Store an item in the cache
     * 
     * @param string $key The cache key
     * @param mixed $value The value to cache
     * @param int $ttl Time to live in seconds
     * @return bool True if stored successfully
     */
    public function put(string $key, mixed $value, int $ttl): bool;

    /**
     * Remove an item from the cache
     * 
     * @param string $key The cache key
     * @return bool True if removed successfully
     */
    public function forget(string $key): bool;

    /**
     * Check if an item exists in the cache
     */
    public function has(string $key): bool;
}
