<?php

declare(strict_types=1);

namespace Nexus\KycVerification\ValueObjects;

use Nexus\KycVerification\Enums\DueDiligenceLevel;
use Nexus\KycVerification\Enums\RiskLevel;

/**
 * Represents a risk assessment for a party.
 * 
 * @immutable
 */
final readonly class RiskAssessment
{
    /**
     * @param array<RiskFactor> $riskFactors
     * @param array<string> $mitigatingFactors
     */
    public function __construct(
        public string $partyId,
        public RiskLevel $riskLevel,
        public int $riskScore,
        public DueDiligenceLevel $requiredDueDiligence,
        public \DateTimeImmutable $assessedAt,
        public array $riskFactors = [],
        public array $mitigatingFactors = [],
        public ?string $assessedBy = null,
        public ?string $approvedBy = null,
        public ?\DateTimeImmutable $nextReviewDate = null,
        public ?string $notes = null,
        public bool $overridden = false,
        public ?RiskLevel $originalRiskLevel = null,
        public ?string $overrideReason = null,
    ) {}

    /**
     * Check if this is a high risk party
     */
    public function isHighRisk(): bool
    {
        return $this->riskLevel === RiskLevel::HIGH 
            || $this->riskLevel === RiskLevel::VERY_HIGH
            || $this->riskLevel === RiskLevel::PROHIBITED;
    }

    /**
     * Check if Enhanced Due Diligence is required
     */
    public function requiresEdd(): bool
    {
        return $this->requiredDueDiligence === DueDiligenceLevel::ENHANCED;
    }

    /**
     * Check if senior approval is needed
     */
    public function requiresSeniorApproval(): bool
    {
        return $this->riskLevel->requiresSeniorApproval();
    }

    /**
     * Check if party is blocked from transactions
     */
    public function isBlocked(): bool
    {
        return $this->riskLevel->blocksTransactions();
    }

    /**
     * Get the primary risk factors sorted by score
     * 
     * @return array<RiskFactor>
     */
    public function getPrimaryRiskFactors(int $limit = 3): array
    {
        $factors = $this->riskFactors;
        usort($factors, fn(RiskFactor $a, RiskFactor $b) => $b->score <=> $a->score);

        return array_slice($factors, 0, $limit);
    }

    /**
     * Check if review is overdue
     */
    public function isReviewOverdue(): bool
    {
        if ($this->nextReviewDate === null) {
            return false;
        }

        return $this->nextReviewDate < new \DateTimeImmutable();
    }

    /**
     * Get days until next review
     */
    public function daysUntilReview(): ?int
    {
        if ($this->nextReviewDate === null) {
            return null;
        }

        $now = new \DateTimeImmutable();
        $diff = $now->diff($this->nextReviewDate);

        return $diff->invert === 1 ? -$diff->days : $diff->days;
    }

    /**
     * Check if risk was overridden
     */
    public function wasOverridden(): bool
    {
        return $this->overridden;
    }

    /**
     * Get risk delta if overridden
     */
    public function getRiskDelta(): ?int
    {
        if (!$this->overridden || $this->originalRiskLevel === null) {
            return null;
        }

        return $this->riskScore - $this->originalRiskLevel->baseScore();
    }

    /**
     * Create a new assessment with updated score
     * 
     * @param array<RiskFactor> $additionalFactors
     */
    public function withAdditionalFactors(array $additionalFactors): self
    {
        $newFactors = array_merge($this->riskFactors, $additionalFactors);
        $additionalScore = array_sum(array_map(fn(RiskFactor $f) => $f->score, $additionalFactors));
        $newScore = min(100, $this->riskScore + $additionalScore);

        return new self(
            partyId: $this->partyId,
            riskLevel: RiskLevel::fromScore($newScore),
            riskScore: $newScore,
            requiredDueDiligence: RiskLevel::fromScore($newScore)->dueDiligenceLevel(),
            assessedAt: new \DateTimeImmutable(),
            riskFactors: $newFactors,
            mitigatingFactors: $this->mitigatingFactors,
            assessedBy: $this->assessedBy,
            nextReviewDate: $this->nextReviewDate,
            notes: $this->notes,
        );
    }

    /**
     * Create a low-risk assessment
     */
    public static function lowRisk(string $partyId): self
    {
        return new self(
            partyId: $partyId,
            riskLevel: RiskLevel::LOW,
            riskScore: 10,
            requiredDueDiligence: DueDiligenceLevel::SIMPLIFIED,
            assessedAt: new \DateTimeImmutable(),
        );
    }

    /**
     * Create a high-risk assessment
     * 
     * @param array<RiskFactor> $factors
     */
    public static function highRisk(string $partyId, array $factors = []): self
    {
        $score = 70 + array_sum(array_map(fn(RiskFactor $f) => $f->score, $factors));
        $score = min(100, $score);

        return new self(
            partyId: $partyId,
            riskLevel: RiskLevel::fromScore($score),
            riskScore: $score,
            requiredDueDiligence: DueDiligenceLevel::ENHANCED,
            assessedAt: new \DateTimeImmutable(),
            riskFactors: $factors,
        );
    }

    /**
     * Convert to array
     * 
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'party_id' => $this->partyId,
            'risk_level' => $this->riskLevel->value,
            'risk_level_label' => $this->riskLevel->label(),
            'risk_score' => $this->riskScore,
            'required_due_diligence' => $this->requiredDueDiligence->value,
            'assessed_at' => $this->assessedAt->format('c'),
            'assessed_by' => $this->assessedBy,
            'approved_by' => $this->approvedBy,
            'next_review_date' => $this->nextReviewDate?->format('Y-m-d'),
            'is_high_risk' => $this->isHighRisk(),
            'requires_edd' => $this->requiresEdd(),
            'requires_senior_approval' => $this->requiresSeniorApproval(),
            'is_blocked' => $this->isBlocked(),
            'risk_factors' => array_map(fn(RiskFactor $f) => $f->toArray(), $this->riskFactors),
            'mitigating_factors' => $this->mitigatingFactors,
            'notes' => $this->notes,
            'overridden' => $this->overridden,
            'original_risk_level' => $this->originalRiskLevel?->value,
            'override_reason' => $this->overrideReason,
        ];
    }
}
