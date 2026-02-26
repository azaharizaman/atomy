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

    public function findAll(): array;

    public function findAllRoles(): array;

    public function findRolePermissions(string $roleId): array;

    public function exists(string $permission): bool;

    public function roleExists(string $roleId): bool;
}
