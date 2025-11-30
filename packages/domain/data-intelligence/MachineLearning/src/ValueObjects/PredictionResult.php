<?php

declare(strict_types=1);

namespace Nexus\MachineLearning\ValueObjects;

use DateTimeImmutable;
use Nexus\MachineLearning\Contracts\PredictionResultInterface;

/**
 * Prediction result value object
 */
final readonly class PredictionResult implements PredictionResultInterface
{
    /**
     * @param float $value Predicted value
     * @param float $confidenceScore Raw confidence (0-1)
     * @param float $calibratedConfidence Calibrated confidence (0-1)
     * @param array<string, mixed> $metadata Additional metadata
     * @param DateTimeImmutable $generatedAt Generation timestamp
     * @param string $modelVersion Model version identifier
     * @param array<string, float> $featureImportance Feature importance scores
     */
    public function __construct(
        private float $value,
        private float $confidenceScore,
        private float $calibratedConfidence,
        private array $metadata,
        private DateTimeImmutable $generatedAt,
        private string $modelVersion,
        private array $featureImportance = []
    ) {}

    public function getValue(): float
    {
        return $this->value;
    }

    public function getConfidenceScore(): float
    {
        return $this->confidenceScore;
    }

    public function getCalibratedConfidence(): float
    {
        return $this->calibratedConfidence;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function getGeneratedAt(): DateTimeImmutable
    {
        return $this->generatedAt;
    }

    public function getModelVersion(): string
    {
        return $this->modelVersion;
    }

    public function getFeatureImportance(): array
    {
        return $this->featureImportance;
    }
}
