<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Nexus\Period\Contracts\CacheRepositoryInterface;

/**
 * Laravel Cache Adapter
 * 
 * Implements CacheRepositoryInterface using Laravel's Cache facade.
 */
final class LaravelCacheAdapter implements CacheRepositoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function get(string $key): mixed
    {
        return Cache::get($key);
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
    public function forget(string $key): bool
    {
        return Cache::forget($key);
    }

    /**
     * {@inheritDoc}
     */
    public function has(string $key): bool
    {
        return Cache::has($key);
    }
}
