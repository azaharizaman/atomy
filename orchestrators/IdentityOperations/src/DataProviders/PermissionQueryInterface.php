<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\DataProviders;

/**
 * Interface for querying permission data.
 */
interface PermissionQueryInterface
{
    /**
     * @return array<int, string>
     */
    public function getUserPermissions(string $userId, string $tenantId): array;

    /**
     * @return array<int, string>
     */
    public function getUserRoles(string $userId, string $tenantId): array;

    public function findAll(string $tenantId): array;

    public function findAllRoles(string $tenantId): array;

    public function findRolePermissions(string $roleId, string $tenantId): array;

    public function exists(string $permission, string $tenantId): bool;

    public function roleExists(string $roleId, string $tenantId): bool;
}
