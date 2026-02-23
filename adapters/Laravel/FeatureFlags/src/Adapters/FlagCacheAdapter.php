<?php

declare(strict_types=1);

namespace Nexus\Laravel\FeatureFlags\Adapters;

use Nexus\FeatureFlags\Contracts\FlagCacheInterface;
use Nexus\FeatureFlags\Contracts\FlagDefinitionInterface;
use Illuminate\Contracts\Cache\Store;
use Psr\Log\LoggerInterface;

/**
 * Laravel implementation of FlagCacheInterface.
 */
class FlagCacheAdapter implements FlagCacheInterface
{
    private const DEFAULT_TTL = 300; // 5 minutes

    public function __construct(
        private readonly Store $cache,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * {@inheritdoc}
     */
    public function get(string $key, ?FlagDefinitionInterface $default = null): ?FlagDefinitionInterface
    {
        $value = $this->cache->get($key);
        return $value ?? $default;
    }

    /**
     * {@inheritdoc}
     */
    public function set(string $key, FlagDefinitionInterface $value, int $ttl): bool
    {
        return $this->cache->put($key, $value, $ttl);
    }

    /**
     * {@inheritdoc}
     */
    public function getMultiple(array $keys, ?FlagDefinitionInterface $default = null): array
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->get($key, $default);
        }
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $key): bool
    {
        $this->cache->forget($key);
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteMultiple(array $keys): bool
    {
        foreach ($keys as $key) {
            $this->cache->forget($key);
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function buildKey(string $flagName, ?string $tenantId): string
    {
        $tenant = $tenantId ?? 'global';
        return "ff:tenant:{$tenant}:flag:{$flagName}";
    }
}
