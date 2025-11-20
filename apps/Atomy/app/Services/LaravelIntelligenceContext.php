<?php

declare(strict_types=1);

namespace App\Services;

use Nexus\Intelligence\Contracts\IntelligenceContextInterface;
use Nexus\Setting\Services\SettingsManager;
use Nexus\Tenant\Contracts\TenantContextInterface;

/**
 * Laravel implementation of intelligence context
 */
final readonly class LaravelIntelligenceContext implements IntelligenceContextInterface
{
    public function __construct(
        private TenantContextInterface $tenantContext,
        private SettingsManager $settings
    ) {}

    public function getCurrentTenantId(): string
    {
        return $this->tenantContext->getCurrentTenantId();
    }

    public function getCurrentUserId(): ?string
    {
        return auth()->id();
    }

    public function hasConsentForTraining(): bool
    {
        return $this->settings->getBool('intelligence.training_consent', false);
    }

    public function getTrainingDataRetentionDays(): int
    {
        return $this->settings->getInt('intelligence.training_retention_days', 365);
    }

    public function isABTestingEnabled(): bool
    {
        return $this->settings->getBool('intelligence.ab_testing.enabled', false);
    }

    public function hasFineTuningQuota(): bool
    {
        return $this->settings->getBool('intelligence.fine_tuning.enabled', false);
    }

    public function getCalibrationEnabled(): bool
    {
        return $this->settings->getBool('intelligence.calibration.enabled', true);
    }
}
