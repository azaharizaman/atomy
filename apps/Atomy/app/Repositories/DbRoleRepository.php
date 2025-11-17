<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Role;
use App\Models\Permission;
use Nexus\Identity\Contracts\RoleInterface;
use Nexus\Identity\Contracts\RoleRepositoryInterface;
use Nexus\Identity\Contracts\PermissionInterface;
use Nexus\Identity\Exceptions\RoleNotFoundException;
use Nexus\Identity\Exceptions\RoleInUseException;

/**
 * Database role repository implementation
 */
final readonly class DbRoleRepository implements RoleRepositoryInterface
{
    public function findById(string $id): RoleInterface
    {
        $role = Role::find($id);

        if (!$role) {
            throw new RoleNotFoundException($id);
        }

        return $role;
    }

    public function findByName(string $name, ?string $tenantId = null): RoleInterface
    {
        $query = Role::where('name', $name);

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        $role = $query->first();

        if (!$role) {
            throw new RoleNotFoundException($name);
        }

        return $role;
    }

    public function findByNameOrNull(string $name, ?string $tenantId = null): ?RoleInterface
    {
        $query = Role::where('name', $name);

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        return $query->first();
    }

    public function create(array $data): RoleInterface
    {
        return Role::create($data);
    }

    public function update(string $id, array $data): RoleInterface
    {
        $role = Role::findOrFail($id);
        $role->update($data);
        return $role->fresh();
    }

    public function delete(string $id): bool
    {
        $role = Role::findOrFail($id);

        if ($role->is_system_role) {
            throw new \InvalidArgumentException('Cannot delete system role');
        }

        return (bool) $role->delete();
    }

    public function nameExists(string $name, ?string $tenantId = null, ?string $excludeRoleId = null): bool
    {
        $query = Role::where('name', $name);

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        if ($excludeRoleId) {
            $query->where('id', '!=', $excludeRoleId);
        }

        return $query->exists();
    }

    public function getRolePermissions(string $roleId): array
    {
        $role = Role::with('permissions')->findOrFail($roleId);
        return $role->permissions->all();
    }

    public function assignPermission(string $roleId, string $permissionId): void
    {
        $role = Role::findOrFail($roleId);
        $permission = Permission::findOrFail($permissionId);

        if (!$role->permissions()->where('permission_id', $permissionId)->exists()) {
            $role->permissions()->attach($permissionId);
        }
    }

    public function revokePermission(string $roleId, string $permissionId): void
    {
        $role = Role::findOrFail($roleId);
        $role->permissions()->detach($permissionId);
    }

    public function getAll(?string $tenantId = null): array
    {
        $query = Role::query();

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        return $query->get()->all();
    }

    public function getRoleHierarchy(?string $tenantId = null): array
    {
        $query = Role::query()->whereNotNull('parent_role_id');

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        $roles = $query->get();
        $hierarchy = [];

        foreach ($roles as $role) {
            $hierarchy[$role->id] = $role->parent_role_id;
        }

        return $hierarchy;
    }

    public function hasUsers(string $roleId): bool
    {
        $role = Role::withCount('users')->findOrFail($roleId);
        return $role->users_count > 0;
    }

    public function countUsers(string $roleId): int
    {
        $role = Role::withCount('users')->findOrFail($roleId);
        return $role->users_count;
    }
}
