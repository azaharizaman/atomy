<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\Contracts;

/**
 * Interface for tenant user data provider.
 */
interface TenantUserProviderInterface
{
    /**
     * Get users for a tenant.
     *
     * @return array<int, array{id: string, email: string, name: string, status: string}>
     */
    public function getTenantUsers(string $tenantId): array;

    /**
     * Check if user belongs to tenant.
     */
    public function userBelongsToTenant(string $userId, string $tenantId): bool;

    /**
     * Get user tenant roles.
     *
     * @return array<int, string>
     */
    public function getUserTenantRoles(string $userId, string $tenantId): array;

    /**
     * Check if tenant exists and is active.
     */
    public function isTenantActive(string $tenantId): bool;
}
