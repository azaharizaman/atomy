<?php

declare(strict_types=1);

namespace Nexus\TenantOperations\DataProviders;

use Nexus\TenantOperations\Contracts\TenantSettingsProviderInterface;

/**
 * Data provider for tenant settings.
 */
final readonly class TenantSettingsDataProvider implements TenantSettingsProviderInterface
{
    public function __construct(
        private SettingsQueryInterface $settingsQuery,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function getSettings(string $tenantId, ?string $key = null): array
    {
        return $this->settingsQuery->getSettings($tenantId, $key);
    }

    public function settingExists(string $tenantId, string $key): bool
    {
        $settings = $this->settingsQuery->getSettings($tenantId, $key);
        return !empty($settings);
    }

    /**
     * @return array<string, mixed>
     */
    public function getDefaultSettings(string $plan): array
    {
        return match ($plan) {
            'starter' => [
                'currency' => 'USD',
                'timezone' => 'UTC',
                'language' => 'en',
                'date_format' => 'Y-m-d',
                'fiscal_year_start' => '01-01',
            ],
            'professional' => [
                'currency' => 'USD',
                'timezone' => 'UTC',
                'language' => 'en',
                'date_format' => 'Y-m-d',
                'fiscal_year_start' => '01-01',
                'multi_currency' => true,
                'advanced_reporting' => true,
            ],
            'enterprise' => [
                'currency' => 'USD',
                'timezone' => 'UTC',
                'language' => 'en',
                'date_format' => 'Y-m-d',
                'fiscal_year_start' => '01-01',
                'multi_currency' => true,
                'advanced_reporting' => true,
                'multi_entity' => true,
                'custom_branding' => true,
            ],
            default => [],
        };
    }
}
