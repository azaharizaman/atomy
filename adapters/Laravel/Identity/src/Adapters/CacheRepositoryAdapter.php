<?php

declare(strict_types=1);

namespace Nexus\Laravel\Identity\Adapters;

use Nexus\Identity\Contracts\CacheRepositoryInterface;
use Illuminate\Contracts\Cache\Repository;
use Psr\Log\LoggerInterface;

/**
 * Laravel implementation of CacheRepositoryInterface.
 */
class CacheRepositoryAdapter implements CacheRepositoryInterface
{
    public function __construct(
        private readonly Repository $cache,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * {@inheritdoc}
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->cache->get($key, $default);
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
        return $this->cache->remember($key, $ttl, $callback);
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
        return $this->cache->add($key, $value, $ttl);
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
        $success = true;
        foreach ($values as $key => $value) {
            if (!$this->put($key, $value, $ttl)) {
                $success = false;
            }
        }
        return $success;
    }

    /**
     * {@inheritdoc}
     */
    public function forgetMany(array $keys): bool
    {
        $success = true;
        foreach ($keys as $key) {
            if (!$this->forget($key)) {
                $success = false;
            }
        }
        return $success;
    }

    /**
     * {@inheritdoc}
     * 
     * Flushing is intentionally disabled for security reasons.
     * Use forget() or forgetMany() for targeted cache invalidation.
     * 
     * @throws \RuntimeException Always thrown to prevent accidental full cache flush
     */
    public function flush(): bool
    {
        throw new \RuntimeException('Cache flush is disabled for security reasons. Use forget() or forgetMany() for targeted invalidation.');
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
