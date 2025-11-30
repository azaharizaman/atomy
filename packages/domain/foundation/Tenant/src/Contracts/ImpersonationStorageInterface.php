<?php

declare(strict_types=1);

namespace Nexus\Tenant\Contracts;

/**
 * Impersonation Storage Interface
 *
 * Defines contract for storing impersonation state externally (session, cache, database).
 * This allows TenantImpersonationService to remain stateless by delegating state management.
 *
 * Application layer implements this using session storage, cache, or database.
 *
 * @package Nexus\Tenant\Contracts
 */
interface ImpersonationStorageInterface
{
    /**
     * Store impersonation context.
     *
     * @param string $key Storage key (e.g., user ID or session key)
     * @param string $originalTenantId Original tenant ID before impersonation
     * @param string $targetTenantId Target tenant ID being impersonated
     * @param string|null $impersonatorId ID of user performing impersonation
     * @return void
     */
    public function store(
        string $key,
        string $originalTenantId,
        string $targetTenantId,
        ?string $impersonatorId = null
    ): void;

    /**
     * Retrieve impersonation context.
     *
     * @param string $key Storage key
     * @return array{original_tenant_id: string, target_tenant_id: string, impersonator_id: string|null}|null
     */
    public function retrieve(string $key): ?array;

    /**
     * Check if impersonation is active.
     *
     * @param string $key Storage key
     * @return bool
     */
    public function isActive(string $key): bool;

    /**
     * Clear impersonation context.
     *
     * @param string $key Storage key
     * @return void
     */
    public function clear(string $key): void;

    /**
     * Get original tenant ID if impersonating.
     *
     * @param string $key Storage key
     * @return string|null
     */
    public function getOriginalTenantId(string $key): ?string;

    /**
     * Get target tenant ID if impersonating.
     *
     * @param string $key Storage key
     * @return string|null
     */
    public function getTargetTenantId(string $key): ?string;
}
