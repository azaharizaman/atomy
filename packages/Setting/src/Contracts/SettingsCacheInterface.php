<?php

declare(strict_types=1);

namespace Nexus\Setting\Contracts;

/**
 * Cache contract for settings caching operations.
 *
 * This interface defines the caching contract for settings.
 * Implementations are typically framework-specific (e.g., Laravel Cache).
 */
interface SettingsCacheInterface
{
    /**
     * Retrieve a value from cache.
     *
     * @param string $key The cache key
     * @param mixed $default The default value if cache miss
     * @return mixed The cached value or default
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Store a value in cache.
     *
     * @param string $key The cache key
     * @param mixed $value The value to cache
     * @param int|null $ttl Time to live in seconds (null = forever)
     */
    public function set(string $key, mixed $value, ?int $ttl = null): void;

    /**
     * Remove a value from cache.
     *
     * @param string $key The cache key
     */
    public function forget(string $key): void;

    /**
     * Check if a key exists in cache.
     *
     * @param string $key The cache key
     * @return bool True if exists
     */
    public function has(string $key): bool;

    /**
     * Flush all cached values.
     */
    public function flush(): void;

    /**
     * Remember a value in cache, or retrieve it if exists.
     *
     * @param string $key The cache key
     * @param callable $callback Callback to generate value on cache miss
     * @param int|null $ttl Time to live in seconds
     * @return mixed The cached or generated value
     */
    public function remember(string $key, callable $callback, ?int $ttl = null): mixed;

    /**
     * Forget all cache entries matching a pattern/prefix.
     *
     * @param string $pattern The pattern to match (e.g., 'setting:user:123:*')
     */
    public function forgetPattern(string $pattern): void;
}
