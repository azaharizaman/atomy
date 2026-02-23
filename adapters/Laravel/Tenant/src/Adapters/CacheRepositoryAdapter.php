<?php

declare(strict_types=1);

namespace Nexus\Laravel\Tenant\Adapters;

use Nexus\Tenant\Contracts\CacheRepositoryInterface;
use Illuminate\Contracts\Cache\Store;
use Psr\Log\LoggerInterface;

/**
 * Laravel implementation of CacheRepositoryInterface.
 *
 * Uses Laravel's cache system for tenant-related caching.
 */
class CacheRepositoryAdapter implements CacheRepositoryInterface
{
    public function __construct(
        private readonly Store $cache,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * {@inheritdoc}
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->cache->get($key);
        
        if ($value === null) {
            return $default;
        }
        
        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function set(string $key, mixed $value, ?int $ttl = null): void
    {
        $ttl = $ttl ?? config('tenant.cache_ttl', 3600);
        $this->cache->put($key, $value, $ttl);
        
        $this->logger->debug('Cache set', ['key' => $key, 'ttl' => $ttl]);
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $key): void
    {
        $this->cache->forget($key);
        $this->logger->debug('Cache deleted', ['key' => $key]);
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $key): bool
    {
        return $this->cache->has($key);
    }

    /**
     * {@inheritdoc}
     */
    public function remember(string $key, int $ttl, callable $callback): mixed
    {
        $value = $this->cache->remember($key, $ttl, $callback);
        
        $this->logger->debug('Cache remember', ['key' => $key, 'ttl' => $ttl]);
        
        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function flush(string $pattern): void
    {
        // Note: Laravel cache doesn't support pattern-based flushing directly
        // This would need to be implemented with tags or custom logic
        $this->logger->warning('Cache flush by pattern not implemented', ['pattern' => $pattern]);
    }
}
