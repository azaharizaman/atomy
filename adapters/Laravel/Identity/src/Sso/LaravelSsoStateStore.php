<?php

declare(strict_types=1);

namespace Nexus\Laravel\Identity\Sso;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Nexus\IdentityOperations\Services\SsoStateStoreInterface;

final readonly class LaravelSsoStateStore implements SsoStateStoreInterface
{
    public function __construct(private CacheRepository $cache)
    {
    }

    public function put(string $state, array $data, int $ttlSeconds): void
    {
        $this->cache->put($this->key($state), $data, $ttlSeconds);
    }

    public function pull(string $state): ?array
    {
        $key = $this->key($state);
        $val = $this->cache->get($key);
        $this->cache->forget($key);

        return is_array($val) ? $val : null;
    }

    private function key(string $state): string
    {
        return 'oidc_sso_state:' . $state;
    }
}

