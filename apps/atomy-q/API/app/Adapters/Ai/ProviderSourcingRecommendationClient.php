<?php

declare(strict_types=1);

namespace App\Adapters\Ai;

use Nexus\IntelligenceOperations\DTOs\AiStatusSchema;
use Nexus\ProcurementOperations\Contracts\VendorRecommendationLlmInterface;
use Nexus\ProcurementOperations\DTOs\VendorRecommendation\VendorRecommendationRequest;
use Nexus\ProcurementOperations\DTOs\VendorRecommendation\VendorRecommendationScoredCandidate;
use App\Adapters\Ai\Contracts\ProviderAiTransportInterface;

final readonly class ProviderSourcingRecommendationClient implements VendorRecommendationLlmInterface
{
    public function __construct(
        private ProviderAiTransportInterface $transport,
    ) {
    }

    /**
     * @param list<VendorRecommendationScoredCandidate> $candidates
     * @return array<string, mixed>
     */
    public function enrich(VendorRecommendationRequest $request, array $candidates): array
    {
        return $this->transport->invoke(AiStatusSchema::ENDPOINT_GROUP_SOURCING_RECOMMENDATION, [
            'tenant_id' => $request->tenantId,
            'rfq_id' => $request->rfqId,
            'categories' => $request->categories,
            'description' => $request->description,
            'geography' => $request->geography,
            'spend_band' => $request->spendBand,
            'line_item_summary' => $request->lineItemSummary,
            'eligible_candidates' => array_map(
                static fn (VendorRecommendationScoredCandidate $candidate): array => [
                    'vendor_id' => $candidate->vendorId,
                    'vendor_name' => $candidate->vendorName,
                    'fit_score' => $candidate->fitScore,
                    'confidence_band' => $candidate->confidenceBand,
                    'recommended_reason_summary' => $candidate->recommendedReasonSummary,
                    'deterministic_reasons' => $candidate->deterministicReasons,
                    'warning_flags' => $candidate->warningFlags,
                    'warnings' => $candidate->warnings,
                ],
                $candidates,
            ),
        ]);
    }
}
