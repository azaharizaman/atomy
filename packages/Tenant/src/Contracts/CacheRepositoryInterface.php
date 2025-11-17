<?php

declare(strict_types=1);

namespace Nexus\Tenant\Contracts;

/**
 * Cache Repository Interface
 *
 * Defines caching operations for tenant data.
 * This interface must be implemented by the application layer using Laravel's cache.
 *
 * @package Nexus\Tenant\Contracts
 */
interface CacheRepositoryInterface
{
    /**
     * Retrieve an item from the cache.
     *
     * @param string $key
     * @return mixed
     */
    public function get(string $key): mixed;

    /**
     * Store an item in the cache.
     *
     * @param string $key
     * @param mixed $value
     * @param int|null $ttl Time to live in seconds (null = forever)
     * @return bool
     */
    public function set(string $key, mixed $value, ?int $ttl = null): bool;

    /**
     * Remove an item from the cache.
     *
     * @param string $key
     * @return bool
     */
    public function forget(string $key): bool;

    /**
     * Remove all items from the cache.
     *
     * @return bool
     */
    public function flush(): bool;

    /**
     * Check if an item exists in the cache.
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool;

    /**
     * Retrieve an item or store default value if not exists.
     *
     * @param string $key
     * @param callable $callback
     * @param int|null $ttl
     * @return mixed
     */
    public function remember(string $key, callable $callback, ?int $ttl = null): mixed;
}
