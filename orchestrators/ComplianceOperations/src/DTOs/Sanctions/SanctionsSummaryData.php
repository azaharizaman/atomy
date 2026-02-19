<?php

declare(strict_types=1);

namespace Nexus\ComplianceOperations\DTOs\Sanctions;

/**
 * Summary DTO for sanctions dashboard data.
 */
final readonly class SanctionsSummaryData
{
    /**
     * @param string $tenantId Tenant identifier
     * @param int $totalScreenings Total number of screenings
     * @param int $matchesFound Number of matches found
     * @param int $potentialMatches Number of potential matches
     * @param int $pepIdentified Number of PEPs identified
     * @param int $pendingReviews Number of pending reviews
     * @param \DateTimeImmutable $generatedAt Generation timestamp
     */
    public function __construct(
        public string $tenantId,
        public int $totalScreenings,
        public int $matchesFound,
        public int $potentialMatches,
        public int $pepIdentified,
        public int $pendingReviews,
        public \DateTimeImmutable $generatedAt,
    ) {}

    /**
     * Get match rate.
     */
    public function getMatchRate(): float
    {
        if ($this->totalScreenings === 0) {
            return 0.0;
        }
        return round(($this->matchesFound / $this->totalScreenings) * 100, 2);
    }

    /**
     * Convert to array for serialization.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'tenantId' => $this->tenantId,
            'totalScreenings' => $this->totalScreenings,
            'matchesFound' => $this->matchesFound,
            'potentialMatches' => $this->potentialMatches,
            'pepIdentified' => $this->pepIdentified,
            'pendingReviews' => $this->pendingReviews,
            'matchRate' => $this->getMatchRate(),
            'generatedAt' => $this->generatedAt->format('Y-m-d H:i:s'),
        ];
    }
}
