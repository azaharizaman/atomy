<?php

declare(strict_types=1);

namespace Nexus\MachineLearning\ValueObjects;

use Nexus\MachineLearning\Contracts\AnomalyResultInterface;
use Nexus\MachineLearning\Enums\SeverityLevel;

/**
 * Anomaly detection result value object
 */
final readonly class AnomalyResult implements AnomalyResultInterface
{
    /**
     * @param bool $flagged Whether anomaly was detected
     * @param SeverityLevel $severity Severity level
     * @param string $reason Human-readable explanation
     * @param float $confidenceScore Raw confidence (0-1)
     * @param float $calibratedConfidence Calibrated confidence (0-1)
     * @param string|null $ruleUsed Rule name if fallback mode
     * @param array<string, float> $featureImportance Feature importance scores
     * @param string|null $modelVersion Model version identifier
     * @param bool $requiresHumanReview Whether human review is needed
     * @param bool $isAdversarial Whether adversarial input detected
     */
    public function __construct(
        private bool $flagged,
        private SeverityLevel $severity,
        private string $reason,
        private float $confidenceScore,
        private float $calibratedConfidence,
        private ?string $ruleUsed = null,
        private array $featureImportance = [],
        private ?string $modelVersion = null,
        private bool $requiresHumanReview = false,
        private bool $isAdversarial = false
    ) {}

    public function isFlagged(): bool
    {
        return $this->flagged;
    }

    public function getSeverity(): SeverityLevel
    {
        return $this->severity;
    }

    public function getReason(): string
    {
        return $this->reason;
    }

    public function getConfidenceScore(): float
    {
        return $this->confidenceScore;
    }

    public function getCalibratedConfidence(): float
    {
        return $this->calibratedConfidence;
    }

    public function getRuleUsed(): ?string
    {
        return $this->ruleUsed;
    }

    public function getFeatureImportance(): array
    {
        return $this->featureImportance;
    }

    public function getModelVersion(): ?string
    {
        return $this->modelVersion;
    }

    public function requiresHumanReview(): bool
    {
        return $this->requiresHumanReview;
    }

    public function isAdversarial(): bool
    {
        return $this->isAdversarial;
    }

    /**
     * Get top N most important features
     * 
     * @param int $n Number of features
     * @return array<string, float>
     */
    public function getTopFeatures(int $n = 3): array
    {
        $sorted = $this->featureImportance;
        arsort($sorted);
        return array_slice($sorted, 0, $n, true);
    }
}
