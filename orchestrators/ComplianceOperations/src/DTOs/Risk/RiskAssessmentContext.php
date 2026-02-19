<?php

declare(strict_types=1);

namespace Nexus\ComplianceOperations\DTOs\Risk;

/**
 * Context DTO for risk assessment data.
 *
 * Aggregates all risk-related data for compliance workflows.
 */
final readonly class RiskAssessmentContext
{
    /**
     * @param string $tenantId Tenant identifier
     * @param string $partyId Party identifier
     * @param int $combinedRiskScore Combined risk score (0-100)
     * @param int|null $kycRiskScore KYC risk score
     * @param int $amlRiskScore AML risk score
     * @param string $riskLevel Risk level classification
     * @param bool $requiresEdd Whether EDD is required
     * @param array<int, array<string, mixed>> $kycRiskFactors KYC risk factors
     * @param array<int, array<string, mixed>> $amlRiskFactors AML risk factors
     * @param array<string> $recommendations Risk mitigation recommendations
     * @param \DateTimeImmutable $nextReviewDate Next review date
     * @param \DateTimeImmutable $assessedAt Assessment timestamp
     */
    public function __construct(
        public string $tenantId,
        public string $partyId,
        public int $combinedRiskScore,
        public ?int $kycRiskScore,
        public int $amlRiskScore,
        public string $riskLevel,
        public bool $requiresEdd,
        public array $kycRiskFactors,
        public array $amlRiskFactors,
        public array $recommendations,
        public \DateTimeImmutable $nextReviewDate,
        public \DateTimeImmutable $assessedAt,
    ) {}

    /**
     * Check if party is high risk.
     */
    public function isHighRisk(): bool
    {
        return in_array($this->riskLevel, ['high', 'very_high', 'prohibited'], true);
    }

    /**
     * Check if party is blocked.
     */
    public function isBlocked(): bool
    {
        return $this->riskLevel === 'prohibited';
    }

    /**
     * Get all risk factors combined.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getAllRiskFactors(): array
    {
        return array_merge($this->kycRiskFactors, $this->amlRiskFactors);
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
            'partyId' => $this->partyId,
            'combinedRiskScore' => $this->combinedRiskScore,
            'kycRiskScore' => $this->kycRiskScore,
            'amlRiskScore' => $this->amlRiskScore,
            'riskLevel' => $this->riskLevel,
            'requiresEdd' => $this->requiresEdd,
            'kycRiskFactors' => $this->kycRiskFactors,
            'amlRiskFactors' => $this->amlRiskFactors,
            'recommendations' => $this->recommendations,
            'nextReviewDate' => $this->nextReviewDate->format('Y-m-d'),
            'assessedAt' => $this->assessedAt->format('Y-m-d H:i:s'),
        ];
    }
}
