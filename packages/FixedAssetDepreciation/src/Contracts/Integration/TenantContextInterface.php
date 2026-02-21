<?php

declare(strict_types=1);

namespace Nexus\FixedAssetDepreciation\Contracts\Integration;

/**
 * Interface for tenant context integration.
 *
 * This interface defines the contract for integrating with the
 * Nexus\Tenant package to ensure multi-tenant data isolation
 * in the FixedAssetDepreciation package.
 *
 * @package Nexus\FixedAssetDepreciation\Contracts\Integration
 */
interface TenantContextInterface
{
    /**
     * Get current tenant ID.
     *
     * Returns the identifier of the currently active tenant.
     *
     * @return string|null The tenant ID or null if not set
     */
    public function getTenantId(): ?string;

    /**
     * Check if tenant context is set.
     *
     * @return bool True if tenant context is available
     */
    public function hasTenantContext(): bool;

    /**
     * Get current tenant identifier.
     *
     * Returns the tenant identifier to use for scoping
     * all depreciation data operations.
     *
     * @return string The tenant identifier
     * @throws \RuntimeException If tenant context is not set
     */
    public function getCurrentTenant(): string;

    /**
     * Set tenant context.
     *
     * Sets the tenant context for the current operation.
     *
     * @param string $tenantId The tenant identifier
     * @return void
     */
    public function setTenant(string $tenantId): void;

    /**
     * Clear tenant context.
     *
     * Clears the current tenant context.
     *
     * @return void
     */
    public function clearTenant(): void;

    /**
     * Check if user has tenant access.
     *
     * Validates that the current user has access to the
     * specified tenant's data.
     *
     * @param string $tenantId The tenant identifier
     * @return bool True if access is allowed
     */
    public function hasAccessToTenant(string $tenantId): bool;

    /**
     * Get tenant configuration.
     *
     * Returns tenant-specific configuration for depreciation
     * settings.
     *
     * @param string $tenantId The tenant identifier
     * @param string $key The configuration key
     * @param mixed $default Default value if not found
     * @return mixed The configuration value
     */
    public function getTenantConfig(
        string $tenantId,
        string $key,
        mixed $default = null
    ): mixed;

    /**
     * Check if multi-tenant mode is enabled.
     *
     * @return bool True if multi-tenant mode is active
     */
    public function isMultiTenantMode(): bool;

    /**
     * Get all accessible tenant IDs.
     *
     * Returns all tenant IDs that the current user has access to.
     *
     * @return array<string> Array of tenant IDs
     */
    public function getAccessibleTenantIds(): array;

    /**
     * Validate tenant isolation.
     *
     * Ensures that operations are properly scoped to
     * the current tenant.
     *
     * @param string $tenantId The tenant identifier to validate
     * @return bool True if the tenant matches current context
     */
    public function validateTenantIsolation(string $tenantId): bool;

    /**
     * Get tenant currency.
     *
     * Returns the primary currency for a tenant.
     *
     * @param string $tenantId The tenant identifier
     * @return string The currency code
     */
    public function getTenantCurrency(string $tenantId): string;

    /**
     * Get tenant locale.
     *
     * Returns the locale settings for a tenant.
     *
     * @param string $tenantId The tenant identifier
     * @return string The locale code
     */
    public function getTenantLocale(string $tenantId): string;
}
