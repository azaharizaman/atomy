<?php

declare(strict_types=1);

namespace Nexus\Laravel\Setting\Adapters;

use Nexus\Setting\Contracts\SettingsCacheInterface;
use Illuminate\Contracts\Cache\Store;
use Psr\Log\LoggerInterface;

/**
 * Laravel implementation of SettingsCacheInterface.
 */
class SettingsCacheAdapter implements SettingsCacheInterface
{
    private const DEFAULT_TTL = 3600;

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
        return $value ?? $default;
    }

    /**
     * {@inheritdoc}
     */
    public function set(string $key, mixed $value, ?int $ttl = null): void
    {
        $ttl = $ttl ?? self::DEFAULT_TTL;
        $this->cache->put($key, $value, $ttl);
    }

    /**
     * {@inheritdoc}
     */
    public function forget(string $key): void
    {
        $this->cache->forget($key);
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
    public function flush(): void
    {
        // Note: This would require cache tags or a specific implementation
        $this->logger->warning('Settings cache flush not fully implemented');
    }

    /**
     * {@inheritdoc}
     */
    public function remember(string $key, callable $callback, ?int $ttl = null): mixed
    {
        $ttl = $ttl ?? self::DEFAULT_TTL;
        
        if ($this->has($key)) {
            return $this->get($key);
        }
        
        $value = $callback();
        $this->set($key, $value, $ttl);
        
        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function forgetPattern(string $pattern): void
    {
        $this->logger->warning('Cache forgetPattern not implemented', ['pattern' => $pattern]);
    }
}
