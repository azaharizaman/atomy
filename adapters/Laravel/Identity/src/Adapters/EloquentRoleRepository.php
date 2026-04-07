<?php

declare(strict_types=1);

namespace Nexus\Laravel\Identity\Adapters;

use App\Models\Role as RoleModel;
use Nexus\Identity\Contracts\RoleInterface;
use Nexus\Identity\Contracts\RoleRepositoryInterface;
// Assuming you have a mapper for roles, otherwise create it based on your needs
// use Nexus\Laravel\Identity\Mappers\LaravelRoleMapper; 

final readonly class EloquentRoleRepository implements RoleRepositoryInterface
{
    public function findById(string $id): RoleInterface
    {
        // $model = RoleModel::query()->findOrFail($id);
        // return LaravelRoleMapper::fromModel($model);
        throw new \BadMethodCallException('Not implemented');
    }

    public function findByName(string $name, ?string $tenantId = null): RoleInterface
    {
        throw new \BadMethodCallException('Not implemented');
    }

    public function findByNameOrNull(string $name, ?string $tenantId = null): ?RoleInterface
    {
        throw new \BadMethodCallException('Not implemented');
    }

    public function nameExists(string $name, ?string $tenantId = null, ?string $excludeRoleId = null): bool
    {
        return false;
    }

    public function getRolePermissions(string $roleId): array
    {
        return [];
    }

    public function getAll(?string $tenantId = null): array
    {
        return [];
    }

    public function getRoleHierarchy(?string $tenantId = null): array
    {
        return [];
    }

    public function hasUsers(string $roleId): bool
    {
        return false;
    }

    public function countUsers(string $roleId): int
    {
        return 0;
    }

    public function create(array $data): RoleInterface
    {
        throw new \BadMethodCallException('Not implemented');
    }

    public function update(string $id, array $data): RoleInterface
    {
        throw new \BadMethodCallException('Not implemented');
    }

    public function delete(string $id): bool
    {
        return false;
    }

    public function assignPermission(string $roleId, string $permissionId): void
    {
    }

    public function revokePermission(string $roleId, string $permissionId): void
    {
    }
}
