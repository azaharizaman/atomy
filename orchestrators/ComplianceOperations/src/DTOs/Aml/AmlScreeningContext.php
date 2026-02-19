<?php

declare(strict_types=1);

namespace Nexus\ComplianceOperations\DTOs\Aml;

/**
 * Context DTO for AML screening data.
 *
 * Aggregates all AML-related data for compliance workflows.
 */
final readonly class AmlScreeningContext
{
    /**
     * @param string $tenantId Tenant identifier
     * @param string $partyId Party identifier
     * @param array<string, mixed> $riskScore Risk score data
     * @param array<string, mixed>|null $monitoringResult Transaction monitoring result
     * @param array<string, mixed> $sarData SAR data
     * @param bool $requiresEdd Whether EDD is required
     * @param array<string> $recommendations Risk mitigation recommendations
     * @param \DateTimeImmutable $nextReviewDate Next review date
     * @param \DateTimeImmutable $assessedAt Assessment timestamp
     */
    public function __construct(
        public string $tenantId,
        public string $partyId,
        public array $riskScore,
        public ?array $monitoringResult,
        public array $sarData,
        public bool $requiresEdd,
        public array $recommendations,
        public \DateTimeImmutable $nextReviewDate,
        public \DateTimeImmutable $assessedAt,
    ) {}

    /**
     * Check if party is high risk.
     */
    public function isHighRisk(): bool
    {
        return in_array($this->riskScore['riskLevel'] ?? '', ['high', 'very_high', 'prohibited'], true);
    }

    /**
     * Check if SAR has been filed.
     */
    public function hasSarFiled(): bool
    {
        return $this->sarData['hasSar'] ?? false;
    }

    /**
     * Check if SAR is recommended.
     */
    public function isSarRecommended(): bool
    {
        return $this->monitoringResult['sarRecommended'] ?? false;
    }

    /**
     * Get overall risk score.
     */
    public function getOverallRiskScore(): int
    {
        return $this->riskScore['overallScore'] ?? 0;
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
            'riskScore' => $this->riskScore,
            'monitoringResult' => $this->monitoringResult,
            'sarData' => $this->sarData,
            'requiresEdd' => $this->requiresEdd,
            'recommendations' => $this->recommendations,
            'nextReviewDate' => $this->nextReviewDate->format('Y-m-d'),
            'assessedAt' => $this->assessedAt->format('Y-m-d H:i:s'),
        ];
    }
}
