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
    public function getAllPermissions(): array
    {
        return $this->permissionQuery->findAll();
    }

    /**
     * @return array<int, array{id: string, name: string, permissions: array<int, string>}>
     */
    public function getAllRoles(): array
    {
        return $this->permissionQuery->findAllRoles();
    }

    /**
     * @return array<int, string>
     */
    public function getRolePermissions(string $roleId): array
    {
        return $this->permissionQuery->findRolePermissions($roleId);
    }

    public function permissionExists(string $permission): bool
    {
        return $this->permissionQuery->exists($permission);
    }

    public function roleExists(string $roleId): bool
    {
        return $this->permissionQuery->roleExists($roleId);
    }
}

/**
 * Interface for querying permission data.
 */
interface PermissionQueryInterface
{
    /**
     * @return array<int, array{id: string, name: string, description: string}>
     */
    public function findAll(): array;

    /**
     * @return array<int, array{id: string, name: string, permissions: array<int, string>}>
     */
    public function findAllRoles(): array;

    /**
     * @return array<int, string>
     */
    public function findRolePermissions(string $roleId): array;

    public function exists(string $permission): bool;

    public function roleExists(string $roleId): bool;
}
