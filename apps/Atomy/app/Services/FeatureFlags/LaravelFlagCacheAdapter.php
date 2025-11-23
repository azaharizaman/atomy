<?php

declare(strict_types=1);

namespace App\Services\FeatureFlags;

use Illuminate\Support\Facades\Cache;
use Nexus\FeatureFlags\Contracts\FlagCacheInterface;
use Nexus\FeatureFlags\Contracts\FlagDefinitionInterface;

/**
 * Laravel cache adapter for FlagCacheInterface.
 *
 * Bridges Nexus\FeatureFlags caching contract with Laravel's cache facade.
 */
final class LaravelFlagCacheAdapter implements FlagCacheInterface
{
    public function __construct(
        private readonly string $cacheStore = 'redis' // Default to Redis
    ) {
    }

    public function get(string $key, ?FlagDefinitionInterface $default = null): ?FlagDefinitionInterface
    {
        return Cache::store($this->cacheStore)->get($key, $default);
    }

    public function set(string $key, FlagDefinitionInterface $value, int $ttl): bool
    {
        return Cache::store($this->cacheStore)->put($key, $value, $ttl);
    }

    public function getMultiple(array $keys, ?FlagDefinitionInterface $default = null): array
    {
        $cached = Cache::store($this->cacheStore)->many($keys);

        // Fill in defaults for missing keys
        $results = [];
        foreach ($keys as $key) {
            $results[$key] = $cached[$key] ?? $default;
        }

        return $results;
    }

    public function delete(string $key): bool
    {
        return Cache::store($this->cacheStore)->forget($key);
    }

    public function deleteMultiple(array $keys): bool
    {
        foreach ($keys as $key) {
            Cache::store($this->cacheStore)->forget($key);
        }

        return true;
    }

    public function buildKey(string $flagName, ?string $tenantId): string
    {
        $tenant = $tenantId !== null ? "tenant:{$tenantId}" : 'global';

        return "ff:{$tenant}:flag:{$flagName}";
    }
}
