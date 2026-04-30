<?php

declare(strict_types=1);

namespace Nexus\InsightOperations\Contracts;

use Nexus\InsightOperations\DTOs\AiArtifactDto;

interface AiArtifactCachePortInterface
{
    public function get(string $cacheKey): ?AiArtifactDto;

    public function put(string $cacheKey, AiArtifactDto $artifact, int $ttlSeconds): void;
}
