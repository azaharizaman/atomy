<?php

declare(strict_types=1);

namespace Nexus\Laravel\ConnectivityOperations\Adapters;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Nexus\ConnectivityOperations\Contracts\ProviderHealthStoreInterface;

final readonly class CacheProviderHealthStoreAdapter implements ProviderHealthStoreInterface
{
    private const KEY = 'connectivity_operations.provider_health';

    public function __construct(private CacheRepository $cache) {}

    public function record(string $providerId, array $snapshot): void
    {
        if (method_exists($this->cache, 'lock')) {
            $lock = $this->cache->lock(self::KEY . ':lock', 5);
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
        }

        $all = $this->all();
        $all[$providerId] = $snapshot;

        $this->cache->put(self::KEY, $all, 86400);
    }

    public function all(): array
    {
        $all = $this->cache->get(self::KEY, []);

        return is_array($all) ? $all : [];
    }
}
