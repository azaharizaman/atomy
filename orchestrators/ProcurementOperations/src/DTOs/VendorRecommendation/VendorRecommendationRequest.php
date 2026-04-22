<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DTOs\VendorRecommendation;

final readonly class VendorRecommendationRequest
{
    /**
     * @param list<string> $categories
     * @param list<string> $lineItemSummary
     * @param list<VendorRecommendationCandidate> $candidates
     */
    public function __construct(
        public string $tenantId,
        public string $rfqId,
        public array $categories,
        public string $description,
        public ?string $geography,
        public ?string $spendBand,
        public array $lineItemSummary,
        public array $candidates,
    ) {
    }
}
