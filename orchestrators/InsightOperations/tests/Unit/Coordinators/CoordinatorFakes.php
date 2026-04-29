<?php

declare(strict_types=1);

namespace Nexus\InsightOperations\Tests\Unit\Coordinators;

use Nexus\InsightOperations\Contracts\AiArtifactCachePortInterface;
use Nexus\InsightOperations\Contracts\AiAvailabilityPortInterface;
use Nexus\InsightOperations\Contracts\InsightNarrativePortInterface;
use Nexus\InsightOperations\DTOs\AiArtifactDto;

final class TrackingNarrativePort implements InsightNarrativePortInterface
{
    public int $calls = 0;
    public ?string $featureKey = null;
    public ?string $tenantId = null;
    public ?string $actorId = null;
    public ?string $subjectType = null;
    /** @var array<string, mixed> */
    public array $facts = [];
    public ?AiArtifactDto $artifact = null;

    public function generate(string $featureKey, string $tenantId, string $subjectType, string $actorId, array $facts): AiArtifactDto
    {
        $this->calls++;
        $this->featureKey = $featureKey;
        $this->tenantId = $tenantId;
        $this->actorId = $actorId;
        $this->subjectType = $subjectType;
        $this->facts = $facts;
        $this->artifact = AiArtifactDto::available(
            featureKey: $featureKey,
            capabilityGroup: str_starts_with($featureKey, 'governance_') ? 'governance_intelligence' : 'insight_intelligence',
            payload: ['summary' => 'Generated AI narrative.'],
        );

        return $this->artifact;
    }
}

final class InMemoryArtifactCache implements AiArtifactCachePortInterface
{
    /** @var array<string, AiArtifactDto> */
    public array $stored = [];

    public function get(string $cacheKey): ?AiArtifactDto
    {
        return $this->stored[$cacheKey] ?? null;
    }

    public function put(string $cacheKey, AiArtifactDto $artifact, int $ttlSeconds): void
    {
        $this->stored[$cacheKey] = $artifact;
    }
}

final class AvailableAiFake implements AiAvailabilityPortInterface
{
    public function isFeatureAvailable(string $featureKey): bool
    {
        return true;
    }

    public function reasonCodes(string $featureKey): array
    {
        return [];
    }
}

final readonly class UnavailableAiFake implements AiAvailabilityPortInterface
{
    /**
     * @param list<string> $reasonCodes
     */
    public function __construct(private array $reasonCodes) {}

    public function isFeatureAvailable(string $featureKey): bool
    {
        return false;
    }

    public function reasonCodes(string $featureKey): array
    {
        return $this->reasonCodes;
    }
}
