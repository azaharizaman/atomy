<?php

declare(strict_types=1);

namespace App\Services\Finance;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Nexus\Finance\Contracts\CacheInterface;

/**
 * Redis Cache Adapter for Finance Package
 * 
 * Laravel-specific implementation of CacheInterface using Redis.
 */
final readonly class RedisCacheAdapter implements CacheInterface
{
    /**
     * {@inheritDoc}
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return Cache::get($key, $default);
    }

    /**
     * {@inheritDoc}
     */
    public function put(string $key, mixed $value, int $ttl): bool
    {
        return Cache::put($key, $value, $ttl);
    }

    /**
     * {@inheritDoc}
     */
    public function remember(string $key, int $ttl, callable $callback): mixed
    {
        return Cache::remember($key, $ttl, $callback);
    }

    /**
     * {@inheritDoc}
     */
    public function forget(string $key): bool
    {
        return Cache::forget($key);
    }

    /**
     * {@inheritDoc}
     */
    public function forgetByPattern(string $pattern): int
    {
        $prefix = config('cache.prefix') ? config('cache.prefix') . ':' : '';
        $keys = Redis::keys($prefix . $pattern);

        if (empty($keys)) {
            return 0;
        }

        foreach ($keys as $key) {
            $cleanKey = str_replace($prefix, '', $key);
            Cache::forget($cleanKey);
        }

        return count($keys);
    }
}
