<?php

declare(strict_types=1);

namespace Nexus\InsightOperations\Coordinators;

use Throwable;
use Psr\Log\LoggerInterface;

use Nexus\InsightOperations\Contracts\AiArtifactCachePortInterface;
use Nexus\InsightOperations\Contracts\AiAvailabilityPortInterface;
use Nexus\InsightOperations\Contracts\DashboardFactsPortInterface;
use Nexus\InsightOperations\Contracts\DashboardInsightCoordinatorInterface;
use Nexus\InsightOperations\Contracts\FactHasherInterface;
use Nexus\InsightOperations\Contracts\InsightNarrativePortInterface;
use Nexus\InsightOperations\DTOs\AiArtifactDto;
use Nexus\InsightOperations\DTOs\InsightResultDto;
use Nexus\InsightOperations\Services\FactHasher;

final readonly class DashboardInsightCoordinator implements
    DashboardInsightCoordinatorInterface
{
    private const FEATURE_KEY = "dashboard_ai_summary";
    private const CAPABILITY_GROUP = "insight_intelligence";
    private const SUBJECT_TYPE = "dashboard_kpis";
    private const ARTIFACT_FIELD = "ai_summary";

    public function __construct(
        private DashboardFactsPortInterface $factsPort,
        private AiArtifactCachePortInterface $cachePort,
        private AiAvailabilityPortInterface $availabilityPort,
        private InsightNarrativePortInterface $narrativePort,
        private LoggerInterface $logger,
        private FactHasherInterface $factHasher = new FactHasher(),
        private int $artifactTtlSeconds = 3600,
    ) {}

    public function show(string $tenantId): InsightResultDto
    {
        $facts = $this->factsPort->factsForTenant($tenantId)->toArray();
        $sourceFactsHash = $this->factHasher->hash($facts);
        $artifact =
            $this->cachePort->get(
                $this->cacheKey($tenantId, $sourceFactsHash),
            ) ??
            $this->unavailable($facts, $sourceFactsHash, [
                "no_cached_ai_artifact",
            ]);

        return new InsightResultDto($facts, $artifact, self::ARTIFACT_FIELD);
    }

    public function generate(
        string $tenantId,
        string $actorId,
    ): InsightResultDto {
        $facts = $this->factsPort->factsForTenant($tenantId)->toArray();
        $sourceFactsHash = $this->factHasher->hash($facts);

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
        } catch (Throwable $e) {
            $this->logger->error(
                "Dashboard insight narrative generation failed.",
                [
                    "feature_key" => self::FEATURE_KEY,
                    "tenant_id" => $tenantId,
                    "actor_id" => $actorId,
                    "source_facts_hash" => $sourceFactsHash,
                    "exception_class" => $e::class,
                    "exception_message" => $e->getMessage(),
                    "exception_trace" => $e->getTraceAsString(),
                ],
            );

            return new InsightResultDto(
                $facts,
                $this->unavailable($facts, $sourceFactsHash, [
                    "provider_unavailable",
                ]),
                self::ARTIFACT_FIELD,
            );
        }

        $this->cachePort->put(
            $this->cacheKey($tenantId, $sourceFactsHash),
            $artifact,
            $this->artifactTtlSeconds,
        );

        return new InsightResultDto($facts, $artifact, self::ARTIFACT_FIELD);
    }

    private function cacheKey(string $tenantId, string $sourceFactsHash): string
    {
        return self::FEATURE_KEY .
            ":" .
            hash(
                "sha256",
                json_encode(
                    [$tenantId, self::SUBJECT_TYPE, $sourceFactsHash],
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
