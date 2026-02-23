<?php

declare(strict_types=1);

namespace Nexus\TenantOperations\DTOs;

/**
 * Context DTO for tenant data aggregation.
 */
final readonly class TenantContext
{
    /**
     * @param array<string, mixed> $settings
     * @param array<string, bool> $features
     */
    public function __construct(
        public ?string $tenantId,
        public ?string $tenantCode,
        public ?string $tenantName,
        public ?string $status,
        public array $settings = [],
        public array $features = [],
        public ?string $plan = null,
    ) {}

    /**
     * Check if tenant is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if a feature is enabled.
     */
    public function isFeatureEnabled(string $featureKey): bool
    {
        return $this->features[$featureKey] ?? false;
    }

    /**
     * Get a setting value.
     */
    public function getSetting(string $key, mixed $default = null): mixed
    {
        return $this->settings[$key] ?? $default;
    }
}
