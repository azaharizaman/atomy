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
    public function getAllPermissions(): array;

    /**
     * Get all available roles.
     *
     * @return array<int, array{id: string, name: string, permissions: array<int, string>}>
     */
    public function getAllRoles(): array;

    /**
     * Get permissions for a role.
     *
     * @return array<int, string>
     */
    public function getRolePermissions(string $roleId): array;

    /**
     * Check if permission exists.
     */
    public function permissionExists(string $permission): bool;

    /**
     * Check if role exists.
     */
    public function roleExists(string $roleId): bool;
}
