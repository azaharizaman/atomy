<?php

declare(strict_types=1);

namespace Nexus\Intelligence\Contracts;

/**
 * Intelligence context interface
 * 
 * Provides runtime context for AI operations
 */
interface IntelligenceContextInterface
{
    /**
     * Get current tenant ID
     * 
     * @return string
     */
    public function getCurrentTenantId(): string;

    /**
     * Get current user ID
     * 
     * @return string|null
     */
    public function getCurrentUserId(): ?string;

    /**
     * Check if tenant has consent for training data collection
     * 
     * @return bool
     */
    public function hasConsentForTraining(): bool;

    /**
     * Get training data retention period in days
     * 
     * @return int
     */
    public function getTrainingDataRetentionDays(): int;

    /**
     * Check if A/B testing is enabled
     * 
     * @return bool
     */
    public function isABTestingEnabled(): bool;

    /**
     * Check if tenant has fine-tuning quota
     * 
     * @return bool
     */
    public function hasFineTuningQuota(): bool;

    /**
     * Check if confidence calibration is enabled
     * 
     * @return bool
     */
    public function getCalibrationEnabled(): bool;
}
