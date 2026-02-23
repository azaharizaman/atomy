<?php

declare(strict_types=1);

namespace Nexus\SettingsManagement\DataProviders;

use Nexus\SettingsManagement\Contracts\SettingsProviderInterface;
use Nexus\SettingsManagement\Contracts\FeatureFlagProviderInterface;
use Nexus\SettingsManagement\Contracts\FiscalPeriodProviderInterface;

/**
 * Data provider that aggregates all configuration data.
 * Provides a unified view of settings, feature flags, and fiscal periods.
 */
final class ConfigurationDataProvider
{
    public function __construct(
        private readonly SettingsProviderInterface $settingsProvider,
        private readonly FeatureFlagProviderInterface $flagProvider,
        private readonly FiscalPeriodProviderInterface $periodProvider,
    ) {}

    /**
     * Get full configuration for a tenant.
     */
    public function getFullConfiguration(string $tenantId): array
    {
        return [
            'settings' => $this->settingsProvider->getAllSettings($tenantId),
            'feature_flags' => $this->flagProvider->getAllFlags($tenantId),
            'fiscal_calendar' => $this->periodProvider->getCalendarConfig($tenantId),
            'fiscal_periods' => $this->periodProvider->getAllPeriods($tenantId),
        ];
    }

    /**
     * Get configuration summary for a tenant.
     */
    public function getConfigurationSummary(string $tenantId): array
    {
        $settings = $this->settingsProvider->getAllSettings($tenantId);
        $flags = $this->flagProvider->getAllFlags($tenantId);
        $periods = $this->periodProvider->getAllPeriods($tenantId);
        $currentPeriod = $this->periodProvider->getCurrentPeriod($tenantId);

        return [
            'settings_count' => count($settings),
            'feature_flags_count' => count($flags),
            'active_flags_count' => count(array_filter($flags, fn($f) => ($f['enabled'] ?? false))),
            'fiscal_periods_count' => count($periods),
            'current_period' => $currentPeriod ? $currentPeriod['name'] ?? $currentPeriod['id'] : null,
        ];
    }
}
