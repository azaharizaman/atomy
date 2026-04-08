<?php

declare(strict_types=1);

namespace Nexus\Laravel\Identity\DataProviders;

use Nexus\Identity\Contracts\RoleInterface;
use Nexus\Identity\Contracts\RoleQueryInterface;
use Nexus\Identity\Contracts\RoleRepositoryInterface;

final readonly class LaravelRoleQuery implements RoleQueryInterface
{
    public function __construct(private RoleRepositoryInterface $roles)
    {
    }

    public function findById(string $id): RoleInterface
    {
        return $this->roles->findById($id);
    }

    public function findByName(string $name, ?string $tenantId = null): RoleInterface
    {
        return $this->roles->findByName($name, $tenantId);
    }

    public function findByNameOrNull(string $name, ?string $tenantId = null): ?RoleInterface
    {
        return $this->roles->findByNameOrNull($name, $tenantId);
    }

    public function nameExists(string $name, ?string $tenantId = null, ?string $excludeRoleId = null): bool
    {
        return $this->roles->nameExists($name, $tenantId, $excludeRoleId);
    }

    public function getRolePermissions(string $roleId): array
    {
        return $this->roles->getRolePermissions($roleId);
    }

    public function getAll(?string $tenantId = null): array
    {
        return $this->roles->getAll($tenantId);
    }

    public function getRoleHierarchy(?string $tenantId = null): array
    {
        return $this->roles->getRoleHierarchy($tenantId);
    }

    public function hasUsers(string $roleId): bool
    {
        return $this->roles->hasUsers($roleId);
    }

    public function countUsers(string $roleId): int
    {
        return $this->roles->countUsers($roleId);
    }
}
