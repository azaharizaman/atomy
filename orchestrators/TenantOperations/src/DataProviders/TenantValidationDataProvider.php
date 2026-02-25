<?php

declare(strict_types=1);

namespace Nexus\TenantOperations\DataProviders;

use Nexus\TenantOperations\Contracts\ConfigurationQueryAdapterInterface;
use Nexus\TenantOperations\Contracts\TenantStatusQueryAdapterInterface;

/**
 * Data provider for tenant validation.
 */
final readonly class TenantValidationDataProvider
{
    public function __construct(
        private TenantStatusQueryAdapterInterface $statusQuery,
        private ConfigurationQueryAdapterInterface $configQuery,
    ) {}

    /**
     * Check if tenant is active.
     */
    public function isTenantActive(string $tenantId): bool
    {
        return $this->statusQuery->isActive($tenantId);
    }

    /**
     * Get tenant status.
     */
    public function getTenantStatus(string $tenantId): ?string
    {
        return $this->statusQuery->getStatus($tenantId);
    }

    /**
     * Check if configuration exists.
     */
    public function configurationExists(string $tenantId, string $configKey): bool
    {
        return $this->configQuery->exists($tenantId, $configKey);
    }

    /**
     * Get configuration value.
     */
    public function getConfiguration(string $tenantId, string $configKey): ?array
    {
        return $this->configQuery->get($tenantId, $configKey);
    }
}
