<?php

declare(strict_types=1);

namespace Nexus\Intelligence\Contracts;

/**
 * Prediction result interface
 */
interface PredictionResultInterface
{
    /**
     * Get predicted value
     * 
     * @return float
     */
    public function getValue(): float;

    /**
     * Get raw confidence score (0-1)
     * 
     * @return float
     */
    public function getConfidenceScore(): float;

    /**
     * Get calibrated confidence score (0-1)
     * 
     * @return float
     */
    public function getCalibratedConfidence(): float;

    /**
     * Get additional metadata
     * 
     * @return array<string, mixed>
     */
    public function getMetadata(): array;

    /**
     * Get generation timestamp
     * 
     * @return \DateTimeImmutable
     */
    public function getGeneratedAt(): \DateTimeImmutable;

    /**
     * Get model version used
     * 
     * @return string
     */
    public function getModelVersion(): string;

    /**
     * Get feature importance scores
     * 
     * @return array<string, float>
     */
    public function getFeatureImportance(): array;
}
