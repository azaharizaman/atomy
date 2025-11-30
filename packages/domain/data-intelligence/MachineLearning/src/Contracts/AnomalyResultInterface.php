<?php

declare(strict_types=1);

namespace Nexus\MachineLearning\Contracts;

/**
 * Anomaly result interface
 */
interface AnomalyResultInterface
{
    /**
     * Check if anomaly was detected
     * 
     * @return bool
     */
    public function isFlagged(): bool;

    /**
     * Get severity level
     * 
     * @return \Nexus\Intelligence\Enums\SeverityLevel
     */
    public function getSeverity(): \Nexus\Intelligence\Enums\SeverityLevel;

    /**
     * Get human-readable reason
     * 
     * @return string
     */
    public function getReason(): string;

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
     * Get rule used (for fallback mode)
     * 
     * @return string|null
     */
    public function getRuleUsed(): ?string;

    /**
     * Get feature importance scores
     * 
     * @return array<string, float> Feature name => importance (0-1)
     */
    public function getFeatureImportance(): array;

    /**
     * Get model version used
     * 
     * @return string|null
     */
    public function getModelVersion(): ?string;

    /**
     * Check if requires human review
     * 
     * @return bool
     */
    public function requiresHumanReview(): bool;

    /**
     * Check if adversarial input detected
     * 
     * @return bool
     */
    public function isAdversarial(): bool;
}
