<?php

declare(strict_types=1);

namespace Nexus\Laravel\Identity\Adapters;

use Nexus\Identity\Contracts\CacheRepositoryInterface;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Cache\Store;
use Psr\Log\LoggerInterface;

/**
 * Laravel implementation of CacheRepositoryInterface.
 */
class CacheRepositoryAdapter implements CacheRepositoryInterface
{
    /**
     * @var Repository
     */
    private $cache;

    public function __construct(
        Repository $cache,
        private readonly LoggerInterface $logger
    ) {
        $this->cache = $cache;
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->cache->get($key);
        return $value ?? $default;
    }

    /**
     * {@inheritdoc}
     */
    public function put(string $key, mixed $value, int $ttl): bool
    {
        return $this->cache->put($key, $value, $ttl);
    }

    /**
     * {@inheritdoc}
     */
    public function remember(string $key, int $ttl, callable $callback): mixed
    {
        // Implement remember logic manually since we're using Repository
        // (Store interface doesn't have remember() method)
        $value = $this->cache->get($key);
        
        if ($value !== null) {
            return $value;
        }
        
        $value = $callback();
        $this->cache->put($key, $value, $ttl);
        
        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function forget(string $key): bool
    {
        return $this->cache->forget($key);
    }

    /**
     * {@inheritdoc}
     */
    public function increment(string $key, int $value = 1): int
    {
        // Laravel cache doesn't support increment directly on all stores
        // This would need a custom implementation
        return $this->cache->increment($key, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function decrement(string $key, int $value = 1): int
    {
        return $this->cache->decrement($key, $value);
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
    public function add(string $key, mixed $value, int $ttl): bool
    {
        // Check if key exists first
        if ($this->has($key)) {
            return false;
        }
        return $this->put($key, $value, $ttl);
    }

    /**
     * {@inheritdoc}
     */
    public function many(array $keys): array
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->get($key);
        }
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function putMany(array $values, int $ttl): bool
    {
        foreach ($values as $key => $value) {
            $this->put($key, $value, $ttl);
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function forgetMany(array $keys): bool
    {
        foreach ($keys as $key) {
            $this->forget($key);
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function flush(): bool
    {
        $this->logger->warning('Cache flush called on Identity cache');
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getTtl(string $key): int
    {
        // Laravel cache doesn't expose TTL directly
        // This would need a custom implementation
        return 0;
    }
}
