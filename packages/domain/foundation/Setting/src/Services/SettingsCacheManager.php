<?php

declare(strict_types=1);

namespace Nexus\Setting\Services;

use Nexus\Setting\Contracts\SettingsCacheInterface;
use Nexus\Setting\ValueObjects\SettingScope;

/**
 * Settings cache manager service.
 *
 * This service provides high-level caching operations for settings
 * with scope-aware cache key management and automatic invalidation.
 */
class SettingsCacheManager
{
    /**
     * Create a new cache manager instance.
     */
    public function __construct(
        private readonly SettingsCacheInterface $cache,
        private readonly int $defaultTtl = 3600,
    ) {
    }

    /**
     * Remember a setting value in cache.
     *
     * @param string $key The setting key
     * @param callable $callback Callback to generate value on cache miss
     * @param int|null $ttl Time to live in seconds (null uses default)
     * @return mixed The cached or generated value
     */
    public function remember(string $key, callable $callback, ?int $ttl = null): mixed
    {
        return $this->cache->remember($key, $callback, $ttl ?? $this->defaultTtl);
    }

    /**
     * Remember a scoped setting value in cache.
     *
     * @param SettingScope $scope The setting scope
     * @param string $key The setting key
     * @param callable $callback Callback to generate value on cache miss
     * @param int|null $ttl Time to live in seconds
     * @return mixed The cached or generated value
     */
    public function rememberScoped(SettingScope $scope, string $key, callable $callback, ?int $ttl = null): mixed
    {
        $cacheKey = $scope->cacheKey($key);

        return $this->cache->remember($cacheKey, $callback, $ttl ?? $this->defaultTtl);
    }

    /**
     * Invalidate cache for a specific setting key.
     *
     * @param string $key The setting key
     */
    public function forget(string $key): void
    {
        $this->cache->forget($key);
    }

    /**
     * Invalidate cache for a scoped setting.
     *
     * @param SettingScope $scope The setting scope
     * @param string $key The setting key
     */
    public function forgetScoped(SettingScope $scope, string $key): void
    {
        $cacheKey = $scope->cacheKey($key);
        $this->cache->forget($cacheKey);
    }

    /**
     * Invalidate all cached settings for a specific scope.
     *
     * @param SettingScope $scope The setting scope
     */
    public function forgetScope(SettingScope $scope): void
    {
        $pattern = $scope->cacheKey('*');
        $this->cache->forgetPattern($pattern);
    }

    /**
     * Flush entire settings cache.
     */
    public function flush(): void
    {
        $this->cache->flush();
    }

    /**
     * Check if a setting is cached.
     *
     * @param string $key The setting key
     * @return bool True if cached
     */
    public function has(string $key): bool
    {
        return $this->cache->has($key);
    }

    /**
     * Check if a scoped setting is cached.
     *
     * @param SettingScope $scope The setting scope
     * @param string $key The setting key
     * @return bool True if cached
     */
    public function hasScoped(SettingScope $scope, string $key): bool
    {
        $cacheKey = $scope->cacheKey($key);

        return $this->cache->has($cacheKey);
    }

    /**
     * Get a value from cache without callback.
     *
     * @param string $key The cache key
     * @param mixed $default The default value if not cached
     * @return mixed The cached value or default
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->cache->get($key, $default);
    }

    /**
     * Store a value in cache.
     *
     * @param string $key The cache key
     * @param mixed $value The value to cache
     * @param int|null $ttl Time to live in seconds
     */
    public function set(string $key, mixed $value, ?int $ttl = null): void
    {
        $this->cache->set($key, $value, $ttl ?? $this->defaultTtl);
    }
}
