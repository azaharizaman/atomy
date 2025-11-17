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
    }

    /**
     * Remember a value in cache, or retrieve it if exists.
     */
    public function remember(string $key, callable $callback, ?int $ttl = null): mixed
    {
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
        $regex = str_replace('*', '.*', $pattern);

        // Get all cache keys (note: this is driver-dependent)
        // For Redis, we can use keys() pattern
        // For other drivers, this may not be efficient
        $keys = Cache::get('_all_setting_keys', []);

        foreach ($keys as $key) {
            if (preg_match("/{$regex}/", $key)) {
                Cache::forget($key);
            }
        }
    }
}
