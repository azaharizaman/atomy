<?php

declare(strict_types=1);

namespace Nexus\TenantOperations\DataProviders;

/**
 * Data provider for tenant validation.
 */
final readonly class TenantValidationDataProvider
{
    public function __construct(
        private TenantStatusQueryInterface $statusQuery,
        private ConfigurationQueryInterface $configQuery,
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

/**
 * Interface for querying tenant status.
 */
interface TenantStatusQueryInterface
{
    public function isActive(string $tenantId): bool;
    public function getStatus(string $tenantId): ?string;
}

/**
 * Interface for querying configuration.
 */
interface ConfigurationQueryInterface
{
    public function exists(string $tenantId, string $configKey): bool;
    public function get(string $tenantId, string $configKey): ?array;
}
