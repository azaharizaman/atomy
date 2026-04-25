<?php

declare(strict_types=1);

namespace App\Adapters\Ai\Contracts;

use Nexus\ProcurementOperations\DTOs\VendorRecommendation\VendorRecommendationRequest;
use Nexus\ProcurementOperations\DTOs\VendorRecommendation\VendorRecommendationScoredCandidate;

interface ProviderSourcingRecommendationClientInterface
{
    /**
     * @param list<VendorRecommendationScoredCandidate> $candidates
     * @return array<string, mixed>
     */
    public function enrich(VendorRecommendationRequest $request, array $candidates): array;
}
