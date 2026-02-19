<?php

declare(strict_types=1);

namespace Nexus\ComplianceOperations\DTOs\Risk;

/**
 * Summary DTO for risk dashboard data.
 */
final readonly class RiskSummaryData
{
    /**
     * @param string $tenantId Tenant identifier
     * @param int $totalAssessed Total parties assessed
     * @param int $highRiskCount Number of high-risk parties
     * @param int $mediumRiskCount Number of medium-risk parties
     * @param int $lowRiskCount Number of low-risk parties
     * @param int $requiresEddCount Number requiring EDD
     * @param int $pendingReviewCount Number pending review
     * @param \DateTimeImmutable $generatedAt Generation timestamp
     */
    public function __construct(
        public string $tenantId,
        public int $totalAssessed,
        public int $highRiskCount,
        public int $mediumRiskCount,
        public int $lowRiskCount,
        public int $requiresEddCount,
        public int $pendingReviewCount,
        public \DateTimeImmutable $generatedAt,
    ) {}

    /**
     * Get risk distribution percentages.
     *
     * @return array<string, float>
     */
    public function getRiskDistribution(): array
    {
        if ($this->totalAssessed === 0) {
            return [
                'high' => 0.0,
                'medium' => 0.0,
                'low' => 0.0,
            ];
        }

        return [
            'high' => round(($this->highRiskCount / $this->totalAssessed) * 100, 2),
            'medium' => round(($this->mediumRiskCount / $this->totalAssessed) * 100, 2),
            'low' => round(($this->lowRiskCount / $this->totalAssessed) * 100, 2),
        ];
    }

    /**
     * Get compliance rate (non-high risk / total).
     */
    public function getComplianceRate(): float
    {
        if ($this->totalAssessed === 0) {
            return 100.0;
        }
        $compliant = $this->totalAssessed - $this->highRiskCount;
        return round(($compliant / $this->totalAssessed) * 100, 2);
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
            'totalAssessed' => $this->totalAssessed,
            'highRiskCount' => $this->highRiskCount,
            'mediumRiskCount' => $this->mediumRiskCount,
            'lowRiskCount' => $this->lowRiskCount,
            'requiresEddCount' => $this->requiresEddCount,
            'pendingReviewCount' => $this->pendingReviewCount,
            'riskDistribution' => $this->getRiskDistribution(),
            'complianceRate' => $this->getComplianceRate(),
            'generatedAt' => $this->generatedAt->format('Y-m-d H:i:s'),
        ];
    }
}
