<?php

declare(strict_types=1);

namespace Nexus\Laravel\Identity\DataProviders;

use Nexus\Identity\Contracts\PermissionQueryInterface as IdentityPermissionQueryInterface;
use Nexus\Identity\Contracts\RoleQueryInterface as IdentityRoleQueryInterface;
use Nexus\IdentityOperations\DataProviders\PermissionQueryInterface;

final readonly class LaravelPermissionQuery implements PermissionQueryInterface
{
    public function __construct(
        private IdentityPermissionQueryInterface $permissions,
        private IdentityRoleQueryInterface $roles,
    ) {
    }

    public function getUserPermissions(string $userId, string $tenantId): array
    {
        // No direct mapping in Identity contracts for "user permissions" here.
        // Keep empty until Identity exposes a suitable query API for it.
        return [];
    }

    public function getUserRoles(string $userId, string $tenantId): array
    {
        // Identity contracts also don't expose "roles for user" query directly here.
        return [];
    }

    public function findAll(string $tenantId): array
    {
        return [];
    }

    public function findAllRoles(string $tenantId): array
    {
        return [];
    }

    public function findRolePermissions(string $roleId, string $tenantId): array
    {
        return [];
    }

    public function exists(string $permission, string $tenantId): bool
    {
        return true;
    }

    public function roleExists(string $roleId, string $tenantId): bool
    {
        return true;
    }
}

