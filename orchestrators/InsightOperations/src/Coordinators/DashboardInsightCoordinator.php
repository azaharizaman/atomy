<?php

declare(strict_types=1);

namespace Nexus\InsightOperations\Coordinators;

use Nexus\InsightOperations\Contracts\AiArtifactCachePortInterface;
use Nexus\InsightOperations\Contracts\AiAvailabilityPortInterface;
use Nexus\InsightOperations\Contracts\DashboardFactsPortInterface;
use Nexus\InsightOperations\Contracts\InsightNarrativePortInterface;
use Nexus\InsightOperations\DTOs\AiArtifactDto;
use Nexus\InsightOperations\DTOs\InsightResultDto;
use Nexus\InsightOperations\Services\FactHasher;

final readonly class DashboardInsightCoordinator
{
    private const FEATURE_KEY = 'dashboard_ai_summary';
    private const CAPABILITY_GROUP = 'insight_intelligence';
    private const SUBJECT_TYPE = 'dashboard';
    private const ARTIFACT_FIELD = 'ai_summary';

    public function __construct(
        private DashboardFactsPortInterface $factsPort,
        private AiArtifactCachePortInterface $cachePort,
        private AiAvailabilityPortInterface $availabilityPort,
        private InsightNarrativePortInterface $narrativePort,
        private FactHasher $factHasher = new FactHasher(),
        private int $artifactTtlSeconds = 3600,
    ) {}

    public function show(string $tenantId): InsightResultDto
    {
        $facts = $this->factsPort->factsForTenant($tenantId)->toArray();
        $sourceFactsHash = $this->factHasher->hash($facts);
        $artifact = $this->cachePort->get($this->cacheKey($tenantId, $sourceFactsHash))
            ?? $this->unavailable($facts, $sourceFactsHash, ['no_cached_ai_artifact']);

        return new InsightResultDto($facts, $artifact, self::ARTIFACT_FIELD);
    }

    public function generate(string $tenantId, string $actorId): InsightResultDto
    {
        $facts = $this->factsPort->factsForTenant($tenantId)->toArray();
        $sourceFactsHash = $this->factHasher->hash($facts);

        if (! $this->availabilityPort->isFeatureAvailable(self::FEATURE_KEY)) {
            return new InsightResultDto(
                $facts,
                $this->unavailable($facts, $sourceFactsHash, $this->availabilityReasonCodes()),
                self::ARTIFACT_FIELD,
            );
        }

        $artifact = $this->narrativePort
            ->generate(self::FEATURE_KEY, $tenantId, self::SUBJECT_TYPE, $actorId, $facts)
            ->withSourceFacts($facts, $sourceFactsHash, $actorId);

        $this->cachePort->put($this->cacheKey($tenantId, $sourceFactsHash), $artifact, $this->artifactTtlSeconds);

        return new InsightResultDto($facts, $artifact, self::ARTIFACT_FIELD);
    }

    private function cacheKey(string $tenantId, string $sourceFactsHash): string
    {
        return self::FEATURE_KEY . ':' . hash('sha256', json_encode([$tenantId, self::SUBJECT_TYPE, $sourceFactsHash], JSON_THROW_ON_ERROR));
    }

    /**
     * @param array<string, mixed> $sourceFacts
     * @param list<string> $reasonCodes
     */
    private function unavailable(array $sourceFacts, string $sourceFactsHash, array $reasonCodes): AiArtifactDto
    {
        return AiArtifactDto::unavailable(
            self::FEATURE_KEY,
            self::CAPABILITY_GROUP,
            $sourceFacts,
            $sourceFactsHash,
            $reasonCodes,
        );
    }

    /**
     * @return list<string>
     */
    private function availabilityReasonCodes(): array
    {
        $reasonCodes = $this->availabilityPort->reasonCodes(self::FEATURE_KEY);

        return $reasonCodes === [] ? ['ai_unavailable'] : $reasonCodes;
    }
}
