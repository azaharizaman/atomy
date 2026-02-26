<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\DataProviders;

use Nexus\IdentityOperations\Contracts\PermissionProviderInterface;

/**
 * Data provider for permission data.
 */
final readonly class PermissionDataProvider implements PermissionProviderInterface
{
    public function __construct(
        private PermissionQueryInterface $permissionQuery,
    ) {}

    /**
     * @return array<int, array{id: string, name: string, description: string}>
     */
    public function getAllPermissions(string $tenantId): array
    {
        return $this->permissionQuery->findAll($tenantId);
    }

    /**
     * @return array<int, array{id: string, name: string, permissions: array<int, string>}>
     */
    public function getAllRoles(string $tenantId): array
    {
        return $this->permissionQuery->findAllRoles($tenantId);
    }

    /**
     * @return array<int, string>
     */
    public function getRolePermissions(string $roleId, string $tenantId): array
    {
        return $this->permissionQuery->findRolePermissions($roleId, $tenantId);
    }

    public function permissionExists(string $permission, string $tenantId): bool
    {
        return $this->permissionQuery->exists($permission, $tenantId);
    }

    public function roleExists(string $roleId, string $tenantId): bool
    {
        return $this->permissionQuery->roleExists($roleId, $tenantId);
    }
}
