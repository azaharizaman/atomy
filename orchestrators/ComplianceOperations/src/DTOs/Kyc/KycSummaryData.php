<?php

declare(strict_types=1);

namespace Nexus\ComplianceOperations\DTOs\Kyc;

/**
 * Summary DTO for KYC dashboard data.
 */
final readonly class KycSummaryData
{
    /**
     * @param string $tenantId Tenant identifier
     * @param int $totalProfiles Total number of KYC profiles
     * @param array<string, int> $statusCounts Count by status
     * @param int $pendingCount Number of pending verifications
     * @param int $highRiskCount Number of high-risk profiles
     * @param int $expiringCount Number of profiles expiring soon
     * @param int $needingReviewCount Number of profiles needing review
     * @param \DateTimeImmutable $generatedAt Generation timestamp
     */
    public function __construct(
        public string $tenantId,
        public int $totalProfiles,
        public array $statusCounts,
        public int $pendingCount,
        public int $highRiskCount,
        public int $expiringCount,
        public int $needingReviewCount,
        public \DateTimeImmutable $generatedAt,
    ) {}

    /**
     * Get compliance rate (verified / total).
     */
    public function getComplianceRate(): float
    {
        if ($this->totalProfiles === 0) {
            return 0.0;
        }

        $verified = $this->statusCounts['verified'] ?? 0;
        return round($verified / $this->totalProfiles * 100, 2);
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
            'totalProfiles' => $this->totalProfiles,
            'statusCounts' => $this->statusCounts,
            'pendingCount' => $this->pendingCount,
            'highRiskCount' => $this->highRiskCount,
            'expiringCount' => $this->expiringCount,
            'needingReviewCount' => $this->needingReviewCount,
            'complianceRate' => $this->getComplianceRate(),
            'generatedAt' => $this->generatedAt->format('Y-m-d H:i:s'),
        ];
    }
}
