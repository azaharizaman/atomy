<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\DataProviders;

/**
 * Interface for querying tenant user data.
 */
interface TenantUserQueryInterface
{
    /**
     * @return array<int, array{id: string, email: string, name: string, status: string}>
     */
    public function findByTenantId(string $tenantId): array;

    public function userBelongsToTenant(string $userId, string $tenantId): bool;

    /**
     * @return array<int, string>
     */
    public function getUserRoles(string $userId, string $tenantId): array;

    public function isTenantActive(string $tenantId): bool;
}
