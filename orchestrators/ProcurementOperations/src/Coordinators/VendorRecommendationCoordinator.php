<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Coordinators;

use Nexus\ProcurementOperations\Contracts\VendorRecommendationCoordinatorInterface;
use Nexus\ProcurementOperations\Contracts\VendorRecommendationLlmInterface;
use Nexus\ProcurementOperations\Contracts\VendorScorerInterface;
use Nexus\ProcurementOperations\DTOs\VendorRecommendation\VendorRecommendationRequest;
use Nexus\ProcurementOperations\DTOs\VendorRecommendation\VendorRecommendationResult;
use Nexus\ProcurementOperations\DTOs\VendorRecommendation\VendorRecommendationScoredCandidate;

final readonly class VendorRecommendationCoordinator implements VendorRecommendationCoordinatorInterface
{
    public const int MAX_LLM_SCORE_DELTA = 10;

    public function __construct(
        private VendorScorerInterface $scorer,
        private VendorRecommendationLlmInterface $llm,
    ) {
    }

    public function recommend(VendorRecommendationRequest $request): VendorRecommendationResult
    {
        $deterministic = $this->scorer->score($request);
        $enrichments = $this->llm->enrich($request, $deterministic->candidates);

        $candidates = [];
        foreach ($deterministic->candidates as $candidate) {
            $candidateEnrichment = isset($enrichments[$candidate->vendorId]) && is_array($enrichments[$candidate->vendorId])
                ? $enrichments[$candidate->vendorId]
                : [];

            if ($candidateEnrichment === []) {
                $candidates[] = $candidate;
                continue;
            }

            $candidates[] = $this->applyEnrichment($candidate, $candidateEnrichment);
        }

        usort(
            $candidates,
            static fn (VendorRecommendationScoredCandidate $a, VendorRecommendationScoredCandidate $b): int =>
                ($b->fitScore <=> $a->fitScore) ?: strcmp($a->vendorName, $b->vendorName),
        );

        return new VendorRecommendationResult(
            tenantId: $deterministic->tenantId,
            rfqId: $deterministic->rfqId,
            candidates: $candidates,
            excludedReasons: $deterministic->excludedReasons,
        );
    }

    /**
     * @param array<string, mixed> $enrichment
     */
    private function applyEnrichment(VendorRecommendationScoredCandidate $candidate, array $enrichment): VendorRecommendationScoredCandidate
    {
        $delta = isset($enrichment['score_delta']) ? (int) $enrichment['score_delta'] : 0;
        $delta = max(-self::MAX_LLM_SCORE_DELTA, min(self::MAX_LLM_SCORE_DELTA, $delta));
        $score = max(0, min(100, $candidate->fitScore + $delta));
        $reasonSummary = isset($enrichment['reason_summary']) && trim((string) $enrichment['reason_summary']) !== ''
            ? trim((string) $enrichment['reason_summary'])
            : $candidate->recommendedReasonSummary;
        $insights = $this->stringList($enrichment['insights'] ?? []);

        return $candidate->withLlmEnrichment($score, $reasonSummary, $insights);
    }

    /**
     * @return list<string>
     */
    private function stringList(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $items = [];
        foreach ($value as $item) {
            $item = trim((string) $item);
            if ($item !== '') {
                $items[] = $item;
            }
        }

        return array_values(array_unique($items));
    }
}
