<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Nexus\Setting\Contracts\SettingsCacheInterface;

/**
 * Laravel cache implementation for settings caching.
 *
 * This service wraps Laravel's cache facade to implement
 * the framework-agnostic SettingsCacheInterface contract.
 */
class LaravelSettingsCacheRepository implements SettingsCacheInterface
{
    private const CACHE_KEYS_REGISTRY = '_all_setting_keys';

    /**
     * Retrieve a value from cache.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return Cache::get($key, $default);
    }

    /**
     * Store a value in cache.
     */
    public function set(string $key, mixed $value, ?int $ttl = null): void
    {
        // Add key to registry
        $this->addKeyToRegistry($key);

        if ($ttl === null) {
            Cache::forever($key, $value);
        } else {
            Cache::put($key, $value, $ttl);
        }
    }

    /**
     * Remove a value from cache.
     */
    public function forget(string $key): void
    {
        Cache::forget($key);
        $this->removeKeyFromRegistry($key);
    }

    /**
     * Check if a key exists in cache.
     */
    public function has(string $key): bool
    {
        return Cache::has($key);
    }

    /**
     * Flush all cached values.
     */
    public function flush(): void
    {
        Cache::flush();
        // Clear registry as well
        Cache::forget(self::CACHE_KEYS_REGISTRY);
    }

    /**
     * Remember a value in cache, or retrieve it if exists.
     */
    public function remember(string $key, callable $callback, ?int $ttl = null): mixed
    {
        // Add key to registry
        $this->addKeyToRegistry($key);

        if ($ttl === null) {
            return Cache::rememberForever($key, $callback);
        }

        return Cache::remember($key, $ttl, $callback);
    }

    /**
     * Forget all cache entries matching a pattern/prefix.
     */
    public function forgetPattern(string $pattern): void
    {
        // Convert glob pattern to regex (e.g., 'setting:user:*' -> 'setting:user:.*')
        $regex = '/^' . str_replace(['*', ':'], ['.*', '\:'], preg_quote($pattern, '/')) . '$/';

        // Get all cache keys from registry
        $keys = Cache::get(self::CACHE_KEYS_REGISTRY, []);

        foreach ($keys as $key) {
            if (preg_match($regex, $key)) {
                Cache::forget($key);
                $this->removeKeyFromRegistry($key);
            }
        }
    }

    /**
     * Add a key to the registry for pattern-based invalidation.
     */
    private function addKeyToRegistry(string $key): void
    {
        $keys = Cache::get(self::CACHE_KEYS_REGISTRY, []);
        
        if (!in_array($key, $keys, true)) {
            $keys[] = $key;
            Cache::forever(self::CACHE_KEYS_REGISTRY, $keys);
        }
    }

    /**
     * Remove a key from the registry.
     */
    private function removeKeyFromRegistry(string $key): void
    {
        $keys = Cache::get(self::CACHE_KEYS_REGISTRY, []);
        $keys = array_filter($keys, fn($k) => $k !== $key);
        Cache::forever(self::CACHE_KEYS_REGISTRY, array_values($keys));
    }
}
