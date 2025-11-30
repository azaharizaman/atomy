<?php

declare(strict_types=1);

namespace Nexus\SSO\Contracts;

use Nexus\SSO\ValueObjects\SsoProviderConfig;

/**
 * SSO configuration repository
 * 
 * Stores and retrieves SSO provider configurations
 */
interface SsoConfigRepositoryInterface
{
    /**
     * Get SSO provider configuration
     * 
     * @param string $providerName Provider identifier
     * @param string $tenantId Tenant context
     * @return SsoProviderConfig Provider configuration
     * @throws \Nexus\SSO\Exceptions\SsoProviderNotFoundException
     */
    public function getConfig(string $providerName, string $tenantId): SsoProviderConfig;

    /**
     * Save SSO provider configuration
     */
    public function saveConfig(SsoProviderConfig $config, string $tenantId): void;

    /**
     * Check if provider is enabled for tenant
     */
    public function isProviderEnabled(string $providerName, string $tenantId): bool;

    /**
     * Get all enabled providers for tenant
     * 
     * @return array<SsoProviderConfig>
     */
    public function getEnabledProviders(string $tenantId): array;

    /**
     * Delete provider configuration
     */
    public function deleteConfig(string $providerName, string $tenantId): void;
}
