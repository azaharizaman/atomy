<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Contracts;

use Nexus\ProcurementOperations\DTOs\VendorRecommendation\VendorRecommendationRequest;
use Nexus\ProcurementOperations\DTOs\VendorRecommendation\VendorRecommendationScoredCandidate;

interface VendorRecommendationLlmInterface
{
    /**
     * @param list<VendorRecommendationScoredCandidate> $candidates
     *
     * @return array<string, array<string, mixed>>
     */
    public function enrich(VendorRecommendationRequest $request, array $candidates): array;
}
