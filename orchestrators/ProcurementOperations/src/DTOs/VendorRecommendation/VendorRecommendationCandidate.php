<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DTOs\VendorRecommendation;

final readonly class VendorRecommendationCandidate
{
    /**
     * @param list<string> $categories
     * @param list<string> $capabilities
     * @param list<string> $regions
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public string $vendorId,
        public string $vendorName,
        public string $status,
        public array $categories = [],
        public array $capabilities = [],
        public array $regions = [],
        public ?string $spendBand = null,
        /** UTC timestamp for the vendor's latest operational activity. */
        public ?\DateTimeImmutable $lastActiveAt = null,
        public int $historicalParticipationCount = 0,
        public int $historicalAwardCount = 0,
        public bool $preferred = false,
        public array $metadata = [],
    ) {
    }
}
