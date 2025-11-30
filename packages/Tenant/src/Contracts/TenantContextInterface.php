<?php

declare(strict_types=1);

namespace Nexus\Tenant\Contracts;

/**
 * Tenant Context Interface
 *
 * Defines the contract for managing the current tenant context within a request or process.
 * This is the primary interface that other packages will use to access tenant information.
 *
 * @package Nexus\Tenant\Contracts
 */
interface TenantContextInterface
{
    /**
     * Set the current active tenant.
     *
     * @param string $tenantId
     * @return void
     * @throws \Nexus\Tenant\Exceptions\TenantNotFoundException
     */
    public function setTenant(string $tenantId): void;

    /**
     * Get the current active tenant ID.
     *
     * @return string|null
     */
    public function getCurrentTenantId(): ?string;

    /**
     * Check if a tenant context is currently set.
     *
     * @return bool
     */
    public function hasTenant(): bool;

    /**
     * Get the current active tenant entity.
     *
     * @return TenantInterface|null
     */
    public function getCurrentTenant(): ?TenantInterface;

    /**
     * Clear the current tenant context.
     *
     * @return void
     */
    public function clearTenant(): void;

    /**
     * Require that a tenant context is set, throw exception if not.
     *
     * @return string The current tenant ID
     * @throws \Nexus\Tenant\Exceptions\TenantContextNotSetException
     */
    public function requireTenant(): string;
}
