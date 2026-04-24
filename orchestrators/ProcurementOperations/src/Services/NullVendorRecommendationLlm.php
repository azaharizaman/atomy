<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Services;

use Nexus\ProcurementOperations\Contracts\VendorRecommendationLlmInterface;
use Nexus\ProcurementOperations\DTOs\VendorRecommendation\VendorRecommendationRequest;

final readonly class NullVendorRecommendationLlm implements VendorRecommendationLlmInterface
{
    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @param list<\Nexus\ProcurementOperations\DTOs\VendorRecommendation\VendorRecommendationScoredCandidate> $candidates
     *
     * @return array<string, mixed>
     */
    public function enrich(VendorRecommendationRequest $request, array $candidates): array
    {
        return [];
    }
}
