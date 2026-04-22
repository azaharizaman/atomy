<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DTOs\VendorRecommendation;

final readonly class VendorRecommendationResult
{
    /**
     * @param list<VendorRecommendationScoredCandidate> $candidates
     * @param list<array{vendor_id: string, vendor_name: string, reason: string}> $excludedReasons
     */
    public function __construct(
        public string $tenantId,
        public string $rfqId,
        public array $candidates,
        public array $excludedReasons = [],
    ) {
    }
}
