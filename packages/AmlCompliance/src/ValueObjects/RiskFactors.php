<?php

declare(strict_types=1);

namespace Nexus\AmlCompliance\ValueObjects;

use Nexus\AmlCompliance\Enums\RiskLevel;

/**
 * Individual Risk Factor Scores
 * 
 * Immutable value object containing the breakdown of risk scores
 * by category for transparency and audit purposes.
 */
final class RiskFactors
{
    /**
     * Weights for each risk factor (must sum to 1.0)
     */
    public const WEIGHT_JURISDICTION = 0.30;
    public const WEIGHT_BUSINESS_TYPE = 0.20;
    public const WEIGHT_SANCTIONS = 0.25;
    public const WEIGHT_TRANSACTION = 0.25;

    /**
     * @param int $jurisdictionScore Geographic/jurisdiction risk score (0-100)
     * @param int $businessTypeScore Business type/industry risk score (0-100)
     * @param int $sanctionsScore Sanctions screening risk score (0-100)
     * @param int $transactionScore Transaction pattern risk score (0-100)
     * @param array<string, mixed> $metadata Additional context for each factor
     */
    public function __construct(
        public readonly int $jurisdictionScore,
        public readonly int $businessTypeScore,
        public readonly int $sanctionsScore,
        public readonly int $transactionScore,
        public readonly array $metadata = [],
    ) {
        $this->validateScore($jurisdictionScore, 'jurisdictionScore');
        $this->validateScore($businessTypeScore, 'businessTypeScore');
        $this->validateScore($sanctionsScore, 'sanctionsScore');
        $this->validateScore($transactionScore, 'transactionScore');
    }

    /**
     * Create with zero scores (new party, no data)
     */
    public static function zero(): self
    {
        return new self(
            jurisdictionScore: 0,
            businessTypeScore: 0,
            sanctionsScore: 0,
            transactionScore: 0,
        );
    }

    /**
     * Create from array
     * 
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            jurisdictionScore: (int) ($data['jurisdictionScore'] ?? $data['jurisdiction_score'] ?? 0),
            businessTypeScore: (int) ($data['businessTypeScore'] ?? $data['business_type_score'] ?? 0),
            sanctionsScore: (int) ($data['sanctionsScore'] ?? $data['sanctions_score'] ?? 0),
            transactionScore: (int) ($data['transactionScore'] ?? $data['transaction_score'] ?? 0),
            metadata: (array) ($data['metadata'] ?? []),
        );
    }

    /**
     * Calculate the weighted composite score
     */
    public function calculateCompositeScore(): int
    {
        $weighted = (
            ($this->jurisdictionScore * self::WEIGHT_JURISDICTION) +
            ($this->businessTypeScore * self::WEIGHT_BUSINESS_TYPE) +
            ($this->sanctionsScore * self::WEIGHT_SANCTIONS) +
            ($this->transactionScore * self::WEIGHT_TRANSACTION)
        );

        return (int) round($weighted);
    }

    /**
     * Get the risk level based on composite score
     */
    public function getRiskLevel(): RiskLevel
    {
        return RiskLevel::fromScore($this->calculateCompositeScore());
    }

    /**
     * Get the highest individual risk score
     */
    public function getMaxScore(): int
    {
        return max(
            $this->jurisdictionScore,
            $this->businessTypeScore,
            $this->sanctionsScore,
            $this->transactionScore
        );
    }

    /**
     * Get the highest risk factor name
     */
    public function getHighestRiskFactor(): string
    {
        $scores = [
            'jurisdiction' => $this->jurisdictionScore,
            'business_type' => $this->businessTypeScore,
            'sanctions' => $this->sanctionsScore,
            'transaction' => $this->transactionScore,
        ];

        return array_keys($scores, max($scores))[0];
    }

    /**
     * Get all factors that exceed a threshold
     * 
     * @return array<string, int>
     */
    public function getFactorsAboveThreshold(int $threshold): array
    {
        $elevated = [];

        if ($this->jurisdictionScore >= $threshold) {
            $elevated['jurisdiction'] = $this->jurisdictionScore;
        }
        if ($this->businessTypeScore >= $threshold) {
            $elevated['business_type'] = $this->businessTypeScore;
        }
        if ($this->sanctionsScore >= $threshold) {
            $elevated['sanctions'] = $this->sanctionsScore;
        }
        if ($this->transactionScore >= $threshold) {
            $elevated['transaction'] = $this->transactionScore;
        }

        return $elevated;
    }

    /**
     * Check if any factor is in high-risk range
     */
    public function hasHighRiskFactor(): bool
    {
        return $this->getMaxScore() >= 70;
    }

    /**
     * Check if sanctions factor is elevated
     */
    public function hasSanctionsRisk(): bool
    {
        return $this->sanctionsScore > 0;
    }

    /**
     * Create a new instance with updated jurisdiction score
     */
    public function withJurisdictionScore(int $score): self
    {
        return new self(
            jurisdictionScore: $score,
            businessTypeScore: $this->businessTypeScore,
            sanctionsScore: $this->sanctionsScore,
            transactionScore: $this->transactionScore,
            metadata: $this->metadata,
        );
    }

    /**
     * Create a new instance with updated business type score
     */
    public function withBusinessTypeScore(int $score): self
    {
        return new self(
            jurisdictionScore: $this->jurisdictionScore,
            businessTypeScore: $score,
            sanctionsScore: $this->sanctionsScore,
            transactionScore: $this->transactionScore,
            metadata: $this->metadata,
        );
    }

    /**
     * Create a new instance with updated sanctions score
     */
    public function withSanctionsScore(int $score): self
    {
        return new self(
            jurisdictionScore: $this->jurisdictionScore,
            businessTypeScore: $this->businessTypeScore,
            sanctionsScore: $score,
            transactionScore: $this->transactionScore,
            metadata: $this->metadata,
        );
    }

    /**
     * Create a new instance with updated transaction score
     */
    public function withTransactionScore(int $score): self
    {
        return new self(
            jurisdictionScore: $this->jurisdictionScore,
            businessTypeScore: $this->businessTypeScore,
            sanctionsScore: $this->sanctionsScore,
            transactionScore: $score,
            metadata: $this->metadata,
        );
    }

    /**
     * Create a new instance with added metadata
     * 
     * @param array<string, mixed> $metadata
     */
    public function withMetadata(array $metadata): self
    {
        return new self(
            jurisdictionScore: $this->jurisdictionScore,
            businessTypeScore: $this->businessTypeScore,
            sanctionsScore: $this->sanctionsScore,
            transactionScore: $this->transactionScore,
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
            'jurisdiction_score' => $this->jurisdictionScore,
            'business_type_score' => $this->businessTypeScore,
            'sanctions_score' => $this->sanctionsScore,
            'transaction_score' => $this->transactionScore,
            'composite_score' => $this->calculateCompositeScore(),
            'risk_level' => $this->getRiskLevel()->value,
            'highest_factor' => $this->getHighestRiskFactor(),
            'metadata' => $this->metadata,
        ];
    }

    /**
     * Validate score is within 0-100 range
     */
    private function validateScore(int $score, string $field): void
    {
        if ($score < 0 || $score > 100) {
            throw new \InvalidArgumentException(
                "Score for {$field} must be between 0 and 100, got {$score}"
            );
        }
    }
}
