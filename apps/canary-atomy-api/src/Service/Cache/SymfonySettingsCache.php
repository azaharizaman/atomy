<?php

declare(strict_types=1);

namespace App\Service\Cache;

use Nexus\Setting\Contracts\SettingsCacheInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Symfony Cache Adapter for Settings.
 */
final readonly class SymfonySettingsCache implements SettingsCacheInterface
{
    public function __construct(
        private CacheInterface $cache
    ) {}

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->cache->get($key, fn (ItemInterface $item) => $default);
    }

    public function set(string $key, mixed $value, ?int $ttl = null): void
    {
        $this->cache->get($key, function (ItemInterface $item) use ($value, $ttl) {
            if ($ttl !== null) {
                $item->expiresAfter($ttl);
            }
            return $value;
        });
    }

    public function forget(string $key): void
    {
        $this->cache->delete($key);
    }

    public function has(string $key): bool
    {
        // Simple implementation for boilerplate
        return $this->get($key) !== null;
    }

    public function flush(): void
    {
        // Symfony CacheInterface doesn't have a simple 'flush all' without specific adapters.
        // For boilerplate, we'll leave this as a no-op or implementation-specific.
    }

    public function remember(string $key, callable $callback, ?int $ttl = null): mixed
    {
        return $this->cache->get($key, function (ItemInterface $item) use ($callback, $ttl) {
            if ($ttl !== null) {
                $item->expiresAfter($ttl);
            }
            return $callback();
        });
    }

    public function forgetPattern(string $pattern): void
    {
        // Symfony Cache doesn't support tags/patterns by default in the basic CacheInterface.
        // For boilerplate, we'll just delete the exact key if it's not a real pattern.
        $this->cache->delete($pattern);
    }
}
