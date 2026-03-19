<?php

declare(strict_types=1);

namespace Nexus\Laravel\Identity\DataProviders;

use Nexus\Identity\Contracts\PermissionQueryInterface as IdentityPermissionQueryInterface;
use Nexus\Identity\Contracts\RoleQueryInterface as IdentityRoleQueryInterface;
use Nexus\Identity\Contracts\UserQueryInterface as IdentityUserQueryInterface;
use Nexus\IdentityOperations\DataProviders\PermissionQueryInterface;

final readonly class LaravelPermissionQuery implements PermissionQueryInterface
{
    public function __construct(
        private IdentityPermissionQueryInterface $permissions,
        private IdentityRoleQueryInterface $roles,
        private IdentityUserQueryInterface $users,
    ) {
    }

    public function getUserPermissions(string $userId, string $tenantId): array
    {
        $this->assertTenantId($tenantId);
        $user = $this->users->findById($userId);
        if ($user->getTenantId() !== $tenantId) {
            return [];
        }

        return array_map(
            static fn ($permission): string => $permission->getName(),
            $this->users->getUserPermissions($userId),
        );
    }

    public function getUserRoles(string $userId, string $tenantId): array
    {
        $this->assertTenantId($tenantId);
        $user = $this->users->findById($userId);
        if ($user->getTenantId() !== $tenantId) {
            return [];
        }

        return array_map(
            static fn ($role): string => $role->getName(),
            $this->users->getUserRoles($userId),
        );
    }

    public function findAll(string $tenantId): array
    {
        $this->assertTenantId($tenantId);
        return array_map(
            static fn ($permission): string => $permission->getName(),
            $this->permissions->getAll(),
        );
    }

    public function findAllRoles(string $tenantId): array
    {
        $this->assertTenantId($tenantId);
        return array_map(
            static fn ($role): string => $role->getName(),
            $this->roles->getAll($tenantId),
        );
    }

    public function findRolePermissions(string $roleId, string $tenantId): array
    {
        $this->assertTenantId($tenantId);
        $role = $this->roles->findById($roleId);
        if ($role->getTenantId() !== null && $role->getTenantId() !== $tenantId) {
            return [];
        }

        return array_map(
            static fn ($permission): string => $permission->getName(),
            $this->roles->getRolePermissions($roleId),
        );
    }

    public function exists(string $permission, string $tenantId): bool
    {
        $this->assertTenantId($tenantId);
        return $this->permissions->findByNameOrNull($permission) !== null;
    }

    public function roleExists(string $roleId, string $tenantId): bool
    {
        $this->assertTenantId($tenantId);

        try {
            $role = $this->roles->findById($roleId);
        } catch (\Throwable) {
            return false;
        }

        return $role->getTenantId() === null || $role->getTenantId() === $tenantId;
    }

    private function assertTenantId(string $tenantId): void
    {
        if (trim($tenantId) === '') {
            throw new \InvalidArgumentException('tenantId is required');
        }
    }
}

