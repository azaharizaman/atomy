<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\Contracts;

/**
 * Interface for permission data provider.
 */
interface PermissionProviderInterface
{
    /**
     * Get all available permissions.
     *
     * @return array<int, array{id: string, name: string, description: string}>
     */
    public function getAllPermissions(string $tenantId): array;

    /**
     * Get all available roles.
     *
     * @return array<int, array{id: string, name: string, permissions: array<int, string>}>
     */
    public function getAllRoles(string $tenantId): array;

    /**
     * Get permissions for a role.
     *
     * @return array<int, string>
     */
    public function getRolePermissions(string $roleId, string $tenantId): array;

    /**
     * Check if permission exists.
     */
    public function permissionExists(string $permission, string $tenantId): bool;

    /**
     * Check if role exists.
     */
    public function roleExists(string $roleId, string $tenantId): bool;
}
