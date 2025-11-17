<?php

declare(strict_types=1);

namespace App\Services;

use Nexus\Tenant\Contracts\CacheRepositoryInterface;
use Illuminate\Support\Facades\Cache;

/**
 * Laravel Cache Repository
 *
 * Implements CacheRepositoryInterface using Laravel's cache system.
 */
class LaravelCacheRepository implements CacheRepositoryInterface
{
    public function get(string $key): mixed
    {
        return Cache::get($key);
    }

    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        if ($ttl === null) {
            return Cache::forever($key, $value);
        }

        return Cache::put($key, $value, $ttl);
    }

    public function forget(string $key): bool
    {
        return Cache::forget($key);
    }

    public function flush(): bool
    {
        return Cache::flush();
    }

    public function has(string $key): bool
    {
        return Cache::has($key);
    }

    public function remember(string $key, callable $callback, ?int $ttl = null): mixed
    {
        if ($ttl === null) {
            return Cache::rememberForever($key, $callback);
        }

        return Cache::remember($key, $ttl, $callback);
    }
}
