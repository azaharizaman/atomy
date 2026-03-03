<?php

declare(strict_types=1);

namespace Nexus\Laravel\ConnectivityOperations\Adapters;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Nexus\ConnectivityOperations\Contracts\ProviderHealthStoreInterface;

final readonly class CacheProviderHealthStoreAdapter implements ProviderHealthStoreInterface
{
    private const KEY = 'connectivity_operations.provider_health';
    private const LOCK_KEY = self::KEY . ':lock';
    private const LOCK_TTL_SECONDS = 5;
    private const LOCK_RETRY_MICROSECONDS = 100_000;
    private const LOCK_RETRIES = 3;

    public function __construct(private CacheRepository $cache) {}

    public function record(string $providerId, array $snapshot): void
    {
        if (!method_exists($this->cache, 'lock')) {
            $all = $this->all();
            $all[$providerId] = $snapshot;
            $this->cache->put(self::KEY, $all, 86400);

            return;
        }

        for ($attempt = 0; $attempt < self::LOCK_RETRIES; $attempt++) {
            $lock = $this->cache->lock(self::LOCK_KEY, self::LOCK_TTL_SECONDS);
            if ($lock->get()) {
                try {
                    $all = $this->all();
                    $all[$providerId] = $snapshot;
                    $this->cache->put(self::KEY, $all, 86400);
                } finally {
                    $lock->release();
                }

                return;
            }

            usleep(self::LOCK_RETRY_MICROSECONDS);
        }

        throw new \RuntimeException('Unable to acquire provider health cache lock.');
    }

    public function all(): array
    {
        $all = $this->cache->get(self::KEY, []);

        return is_array($all) ? $all : [];
    }
}
