<?php

declare(strict_types=1);

namespace Nexus\AmlCompliance\ValueObjects;

use Nexus\AmlCompliance\Enums\RiskLevel;

/**
 * AML Risk Assessment Score Result
 * 
 * Immutable value object representing the complete AML risk score
 * for a party/customer including the breakdown by factors.
 */
final class AmlRiskScore
{
    /**
     * @param string $partyId Identifier of the assessed party
     * @param int $overallScore Composite risk score (0-100)
     * @param RiskLevel $riskLevel Classified risk level
     * @param RiskFactors $factors Individual factor breakdown
     * @param \DateTimeImmutable $assessedAt Timestamp of assessment
     * @param string|null $assessedBy User/system that performed assessment
     * @param \DateTimeImmutable|null $nextReviewDate Recommended next review date
     * @param array<string> $recommendations Risk mitigation recommendations
     * @param array<string, mixed> $metadata Additional assessment data
     */
    public function __construct(
        public readonly string $partyId,
        public readonly int $overallScore,
        public readonly RiskLevel $riskLevel,
        public readonly RiskFactors $factors,
        public readonly \DateTimeImmutable $assessedAt,
        public readonly ?string $assessedBy = null,
        public readonly ?\DateTimeImmutable $nextReviewDate = null,
        public readonly array $recommendations = [],
        public readonly array $metadata = [],
    ) {
        if ($overallScore < 0 || $overallScore > 100) {
            throw new \InvalidArgumentException(
                "Overall score must be between 0 and 100, got {$overallScore}"
            );
        }
    }

    /**
     * Create a new risk score from factors
     */
    public static function fromFactors(
        string $partyId,
        RiskFactors $factors,
        ?string $assessedBy = null,
    ): self {
        $overallScore = $factors->calculateCompositeScore();
        $riskLevel = RiskLevel::fromScore($overallScore);
        $assessedAt = new \DateTimeImmutable();

        return new self(
            partyId: $partyId,
            overallScore: $overallScore,
            riskLevel: $riskLevel,
            factors: $factors,
            assessedAt: $assessedAt,
            assessedBy: $assessedBy,
            nextReviewDate: $assessedAt->modify("+{$riskLevel->getReviewFrequencyDays()} days"),
            recommendations: self::generateRecommendations($factors, $riskLevel),
        );
    }

    /**
     * Create from array (for hydration from storage)
     * 
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            partyId: (string) $data['party_id'],
            overallScore: (int) $data['overall_score'],
            riskLevel: RiskLevel::from($data['risk_level']),
            factors: RiskFactors::fromArray($data['factors'] ?? []),
            assessedAt: $data['assessed_at'] instanceof \DateTimeImmutable
                ? $data['assessed_at']
                : new \DateTimeImmutable($data['assessed_at']),
            assessedBy: $data['assessed_by'] ?? null,
            nextReviewDate: isset($data['next_review_date'])
                ? ($data['next_review_date'] instanceof \DateTimeImmutable
                    ? $data['next_review_date']
                    : new \DateTimeImmutable($data['next_review_date']))
                : null,
            recommendations: $data['recommendations'] ?? [],
            metadata: $data['metadata'] ?? [],
        );
    }

    /**
     * Check if enhanced due diligence is required
     */
    public function requiresEdd(): bool
    {
        return $this->riskLevel->requiresEdd();
    }

    /**
     * Check if enhanced monitoring is required
     */
    public function requiresEnhancedMonitoring(): bool
    {
        return $this->riskLevel->requiresEnhancedMonitoring();
    }

    /**
     * Check if a review is overdue
     */
    public function isReviewOverdue(): bool
    {
        if ($this->nextReviewDate === null) {
            return false;
        }

        return $this->nextReviewDate < new \DateTimeImmutable();
    }

    /**
     * Check if review is due within N days
     */
    public function isReviewDueSoon(int $withinDays = 30): bool
    {
        if ($this->nextReviewDate === null) {
            return false;
        }

        $threshold = (new \DateTimeImmutable())->modify("+{$withinDays} days");
        return $this->nextReviewDate <= $threshold;
    }

    /**
     * Get the days until next review
     */
    public function getDaysUntilReview(): ?int
    {
        if ($this->nextReviewDate === null) {
            return null;
        }

        $now = new \DateTimeImmutable();
        $interval = $now->diff($this->nextReviewDate);

        return $interval->invert === 1 ? -$interval->days : $interval->days;
    }

    /**
     * Check if score has increased (worsened) from previous
     */
    public function hasIncreasedFrom(self $previous): bool
    {
        return $this->overallScore > $previous->overallScore;
    }

    /**
     * Get the change in score from previous assessment
     */
    public function getScoreChange(self $previous): int
    {
        return $this->overallScore - $previous->overallScore;
    }

    /**
     * Check if risk level has escalated
     */
    public function hasEscalatedFrom(self $previous): bool
    {
        return $this->riskLevel->isHigherThan($previous->riskLevel);
    }

    /**
     * Check if there are sanctions concerns
     */
    public function hasSanctionsRisk(): bool
    {
        return $this->factors->hasSanctionsRisk();
    }

    /**
     * Get the primary risk concern
     */
    public function getPrimaryRiskFactor(): string
    {
        return $this->factors->getHighestRiskFactor();
    }

    /**
     * Create a new instance with updated metadata
     * 
     * @param array<string, mixed> $metadata
     */
    public function withMetadata(array $metadata): self
    {
        return new self(
            partyId: $this->partyId,
            overallScore: $this->overallScore,
            riskLevel: $this->riskLevel,
            factors: $this->factors,
            assessedAt: $this->assessedAt,
            assessedBy: $this->assessedBy,
            nextReviewDate: $this->nextReviewDate,
            recommendations: $this->recommendations,
            metadata: array_merge($this->metadata, $metadata),
        );
    }

    /**
     * Convert to array for serialization
     * 
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'party_id' => $this->partyId,
            'overall_score' => $this->overallScore,
            'risk_level' => $this->riskLevel->value,
            'risk_level_description' => $this->riskLevel->getDescription(),
            'requires_edd' => $this->requiresEdd(),
            'requires_enhanced_monitoring' => $this->requiresEnhancedMonitoring(),
            'factors' => $this->factors->toArray(),
            'primary_risk_factor' => $this->getPrimaryRiskFactor(),
            'assessed_at' => $this->assessedAt->format(\DateTimeInterface::ATOM),
            'assessed_by' => $this->assessedBy,
            'next_review_date' => $this->nextReviewDate?->format(\DateTimeInterface::ATOM),
            'days_until_review' => $this->getDaysUntilReview(),
            'is_review_overdue' => $this->isReviewOverdue(),
            'recommendations' => $this->recommendations,
            'metadata' => $this->metadata,
        ];
    }

    /**
     * Generate risk mitigation recommendations based on factors
     * 
     * @return array<string>
     */
    private static function generateRecommendations(RiskFactors $factors, RiskLevel $level): array
    {
        $recommendations = [];

        if ($level->requiresEdd()) {
            $recommendations[] = 'Conduct Enhanced Due Diligence (EDD) before onboarding';
        }

        if ($factors->jurisdictionScore >= 70) {
            $recommendations[] = 'Review jurisdiction risk - consider high-risk country procedures';
            $recommendations[] = 'Obtain source of funds documentation';
        }

        if ($factors->businessTypeScore >= 70) {
            $recommendations[] = 'Apply industry-specific AML controls for high-risk business type';
            $recommendations[] = 'Implement enhanced transaction monitoring thresholds';
        }

        if ($factors->sanctionsScore > 0) {
            $recommendations[] = 'Review sanctions screening results and obtain clarification';
            if ($factors->sanctionsScore >= 50) {
                $recommendations[] = 'Escalate to compliance officer for sanctions review';
            }
        }

        if ($factors->transactionScore >= 70) {
            $recommendations[] = 'Review transaction patterns for suspicious activity';
            $recommendations[] = 'Consider filing Suspicious Activity Report (SAR)';
        }

        if ($level->requiresEnhancedMonitoring()) {
            $recommendations[] = 'Enable enhanced monitoring with lower alert thresholds';
            $recommendations[] = "Schedule periodic review every {$level->getReviewFrequencyDays()} days";
        }

        return $recommendations;
    }
}
