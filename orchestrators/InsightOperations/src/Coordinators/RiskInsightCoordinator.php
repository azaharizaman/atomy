<?php

declare(strict_types=1);

namespace Nexus\InsightOperations\Coordinators;

use Throwable;

use Nexus\InsightOperations\Contracts\AiArtifactCachePortInterface;
use Nexus\InsightOperations\Contracts\AiAvailabilityPortInterface;
use Nexus\InsightOperations\Contracts\RiskInsightCoordinatorInterface;
use Nexus\InsightOperations\Contracts\RiskInsightFactsCommandInterface;
use Nexus\InsightOperations\Contracts\RiskInsightFactsQueryInterface;
use Nexus\InsightOperations\Contracts\InsightNarrativePortInterface;
use Nexus\InsightOperations\Contracts\FactHasherInterface;
use Nexus\InsightOperations\DTOs\AiArtifactDto;
use Nexus\InsightOperations\DTOs\InsightResultDto;

final readonly class RiskInsightCoordinator implements
    RiskInsightCoordinatorInterface
{
    private const FEATURE_KEY = "rfq_ai_insights";
    private const CAPABILITY_GROUP = "insight_intelligence";
    private const SUBJECT_TYPE = "rfq";
    private const ARTIFACT_FIELD = "ai_insights";

    public function __construct(
        private RiskInsightFactsQueryInterface $queryPort,
        private RiskInsightFactsCommandInterface $commandPort,
        private AiArtifactCachePortInterface $cachePort,
        private AiAvailabilityPortInterface $availabilityPort,
        private InsightNarrativePortInterface $narrativePort,
        private FactHasherInterface $factHasher,
        private int $artifactTtlSeconds = 3600,
    ) {}

    public function show(string $tenantId, string $rfqId): InsightResultDto
    {
        $facts = $this->queryPort->factsForRfq($tenantId, $rfqId)->toArray();
        $sourceFactsHash = $this->factHasher->hash($facts);
        $artifact =
            $this->cachePort->get(
                $this->cacheKey($tenantId, $rfqId, $sourceFactsHash),
            ) ??
            $this->unavailable($facts, $sourceFactsHash, [
                "no_cached_ai_artifact",
            ]);

        return new InsightResultDto($facts, $artifact, self::ARTIFACT_FIELD);
    }

    public function generate(
        string $tenantId,
        string $rfqId,
        string $actorId,
    ): InsightResultDto {
        $facts = $this->queryPort->factsForRfq($tenantId, $rfqId)->toArray();
        $sourceFactsHash = $this->factHasher->hash($facts);

        if (($facts["risk_items"] ?? []) === []) {
            return new InsightResultDto(
                $facts,
                $this->unavailable($facts, $sourceFactsHash, [
                    "source_facts_unavailable",
                ]),
                self::ARTIFACT_FIELD,
            );
        }

        if (!$this->availabilityPort->isFeatureAvailable(self::FEATURE_KEY)) {
            return new InsightResultDto(
                $facts,
                $this->unavailable(
                    $facts,
                    $sourceFactsHash,
                    $this->availabilityReasonCodes(),
                ),
                self::ARTIFACT_FIELD,
            );
        }

        try {
            $artifact = $this->narrativePort
                ->generate(
                    self::FEATURE_KEY,
                    $tenantId,
                    self::SUBJECT_TYPE,
                    $actorId,
                    $facts,
                )
                ->withSourceFacts($facts, $sourceFactsHash, $actorId);
        } catch (Throwable) {
            return new InsightResultDto(
                $facts,
                $this->unavailable($facts, $sourceFactsHash, [
                    "provider_unavailable",
                ]),
                self::ARTIFACT_FIELD,
            );
        }

        $this->cachePort->put(
            $this->cacheKey($tenantId, $rfqId, $sourceFactsHash),
            $artifact,
            $this->artifactTtlSeconds,
        );

        return new InsightResultDto($facts, $artifact, self::ARTIFACT_FIELD);
    }

    public function escalate(
        string $tenantId,
        string $rfqId,
        string $itemId,
    ): void {
        $this->commandPort->escalate($tenantId, $rfqId, $itemId);
    }

    public function resolveAsException(
        string $tenantId,
        string $rfqId,
        string $itemId,
        string $actorId,
    ): void {
        $this->commandPort->resolveAsException(
            $tenantId,
            $rfqId,
            $itemId,
            $actorId,
        );
    }

    private function cacheKey(
        string $tenantId,
        string $rfqId,
        string $sourceFactsHash,
    ): string {
        return self::FEATURE_KEY .
            ":" .
            hash(
                "sha256",
                json_encode(
                    [$tenantId, $rfqId, $sourceFactsHash],
                    JSON_THROW_ON_ERROR,
                ),
            );
    }

    /**
     * @param array<string, mixed> $sourceFacts
     * @param list<string> $reasonCodes
     */
    private function unavailable(
        array $sourceFacts,
        string $sourceFactsHash,
        array $reasonCodes,
    ): AiArtifactDto {
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

        return $reasonCodes === [] ? ["ai_unavailable"] : $reasonCodes;
    }
}
