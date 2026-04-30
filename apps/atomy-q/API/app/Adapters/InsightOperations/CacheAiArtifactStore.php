<?php

declare(strict_types=1);

namespace App\Adapters\InsightOperations;

use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Nexus\InsightOperations\Contracts\AiArtifactCachePortInterface;
use Nexus\InsightOperations\DTOs\AiArtifactDto;

final readonly class CacheAiArtifactStore implements AiArtifactCachePortInterface
{
    public function __construct(private CacheFactory $cache)
    {
    }

    public function get(string $cacheKey): ?AiArtifactDto
    {
        $artifact = $this->cache->store()->get($cacheKey);

        return $artifact instanceof AiArtifactDto ? $artifact : null;
    }

    public function put(string $cacheKey, AiArtifactDto $artifact, int $ttlSeconds): void
    {
        $this->cache->store()->put($cacheKey, $artifact, $ttlSeconds);
    }
}
