<?php

declare(strict_types=1);

namespace Nexus\TenantOperations\DTOs;

/**
 * Request DTO for tenant onboarding.
 */
final readonly class TenantOnboardingRequest
{
    public function __construct(
        public string $tenantCode,
        public string $tenantName,
        public string $domain,
        public string $adminEmail,
        public string $adminPassword,
        public string $plan,
        public ?string $currency = null,
        public ?string $timezone = null,
        public ?string $language = null,
        public ?string $dateFormat = null,
        public ?string $fiscalYearStart = null,
        public ?array $metadata = null,
    ) {}

    /**
     * Get default settings for the plan.
     *
     * @return array<string, mixed>
     */
    public function getDefaultSettings(): array
    {
        return [
            'currency' => $this->currency ?? 'USD',
            'timezone' => $this->timezone ?? 'UTC',
            'language' => $this->language ?? 'en',
            'date_format' => $this->dateFormat ?? 'Y-m-d',
            'fiscal_year_start' => $this->fiscalYearStart ?? '01-01',
        ];
    }

    /**
     * Get default features for the plan.
     *
     * @return array<string, bool>
     */
    public function getDefaultFeatures(): array
    {
        return match ($this->plan) {
            'starter' => [
                'finance' => true,
                'hr' => true,
                'sales' => false,
                'procurement' => false,
                'inventory' => false,
                'crm' => false,
                'advanced_reporting' => false,
                'api_access' => false,
            ],
            'professional' => [
                'finance' => true,
                'hr' => true,
                'sales' => true,
                'procurement' => true,
                'inventory' => true,
                'crm' => false,
                'advanced_reporting' => true,
                'api_access' => true,
            ],
            'enterprise' => [
                'finance' => true,
                'hr' => true,
                'sales' => true,
                'procurement' => true,
                'inventory' => true,
                'crm' => true,
                'advanced_reporting' => true,
                'api_access' => true,
                'custom_branding' => true,
                'multi_entity' => true,
            ],
            default => [],
        };
    }
}
