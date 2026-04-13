<?php

declare(strict_types=1);

namespace Nexus\Laravel\Identity\Repositories;

use App\Models\Permission as PermissionModel;
use App\Models\Role as RoleModel;
use App\Models\User as UserModel;
use Illuminate\Support\Facades\DB;
use Nexus\Identity\Contracts\PermissionInterface;
use Nexus\Identity\Contracts\RoleInterface;
use Nexus\Identity\Contracts\RoleRepositoryInterface;
use Nexus\Identity\Exceptions\RoleNotFoundException;

final readonly class EloquentRoleRepository implements RoleRepositoryInterface
{
    public function findById(string $id): RoleInterface
    {
        $role = RoleModel::query()->whereKey($id)->first();
        if ($role === null) {
            throw new RoleNotFoundException($id);
        }

        return $role;
    }

    public function findByName(string $name, ?string $tenantId = null): RoleInterface
    {
        $role = $this->findByNameOrNull($name, $tenantId);
        if ($role === null) {
            throw new RoleNotFoundException($name);
        }

        return $role;
    }

    public function findByNameOrNull(string $name, ?string $tenantId = null): ?RoleInterface
    {
        $query = RoleModel::query()->where('name', trim($name));

        if ($tenantId !== null && trim($tenantId) !== '') {
            $query->where(function ($builder) use ($tenantId): void {
                $builder->whereNull('tenant_id')->orWhere('tenant_id', $tenantId);
            });
        }

        $role = $query->first();

        return $role instanceof RoleModel ? $role : null;
    }

    public function nameExists(string $name, ?string $tenantId = null, ?string $excludeRoleId = null): bool
    {
        $query = RoleModel::query()->where('name', trim($name));

        if ($tenantId !== null && trim($tenantId) !== '') {
            $query->where(function ($builder) use ($tenantId): void {
                $builder->whereNull('tenant_id')->orWhere('tenant_id', $tenantId);
            });
        }

        if ($excludeRoleId !== null && trim($excludeRoleId) !== '') {
            $query->whereKeyNot($excludeRoleId);
        }

        return $query->exists();
    }

    /**
     * @return PermissionInterface[]
     */
    public function getRolePermissions(string $roleId): array
    {
        $permissionIds = DB::table('role_permissions')
            ->where('role_id', $roleId)
            ->pluck('permission_id')
            ->all();

        if ($permissionIds === []) {
            return [];
        }

        return PermissionModel::query()
            ->whereIn('id', $permissionIds)
            ->orderBy('name')
            ->get()
            ->all();
    }

    /**
     * @return RoleInterface[]
     */
    public function getAll(?string $tenantId = null): array
    {
        $query = RoleModel::query();
        if ($tenantId !== null && trim($tenantId) !== '') {
            $query->where(function ($builder) use ($tenantId): void {
                $builder->whereNull('tenant_id')->orWhere('tenant_id', $tenantId);
            });
        }

        return $query
            ->orderBy('name')
            ->get()
            ->all();
    }

    /**
     * @return array<string, string>
     */
    public function getRoleHierarchy(?string $tenantId = null): array
    {
        $query = RoleModel::query();
        if ($tenantId !== null && trim($tenantId) !== '') {
            $query->where(function ($builder) use ($tenantId): void {
                $builder->whereNull('tenant_id')->orWhere('tenant_id', $tenantId);
            });
        }

        $hierarchy = [];
        foreach ($query->get() as $role) {
            $parentRoleId = $role->getParentRoleId();
            if ($parentRoleId !== null) {
                $hierarchy[$role->getId()] = $parentRoleId;
            }
        }

        return $hierarchy;
    }

    public function hasUsers(string $roleId): bool
    {
        if (DB::table('user_roles')->where('role_id', $roleId)->exists()) {
            return true;
        }

        return UserModel::query()->where('role', $roleId)->exists();
    }

    public function countUsers(string $roleId): int
    {
        $userIds = DB::table('user_roles')
            ->where('role_id', $roleId)
            ->pluck('user_id')
            ->all();

        $legacyUserIds = UserModel::query()
            ->where('role', $roleId)
            ->pluck('id')
            ->all();

        return count(array_unique(array_merge($userIds, $legacyUserIds)));
    }

    public function create(array $data): RoleInterface
    {
        return RoleModel::query()->create($this->normalizePayload($data));
    }

    public function update(string $id, array $data): RoleInterface
    {
        $role = RoleModel::query()->whereKey($id)->first();
        if ($role === null) {
            throw new RoleNotFoundException($id);
        }

        $role->fill($this->normalizePayload($data, $role));
        $role->save();

        return $role->fresh() ?? $role;
    }

    public function delete(string $id): bool
    {
        if ($this->hasUsers($id)) {
            return false;
        }

        return RoleModel::query()->whereKey($id)->delete() > 0;
    }

    public function assignPermission(string $roleId, string $permissionId): void
    {
        DB::table('role_permissions')->updateOrInsert(
            [
                'role_id' => $roleId,
                'permission_id' => $permissionId,
            ],
            [
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
    }

    public function revokePermission(string $roleId, string $permissionId): void
    {
        DB::table('role_permissions')
            ->where('role_id', $roleId)
            ->where('permission_id', $permissionId)
            ->delete();
    }

    /**
     * @param array<string, mixed> $data
     * @param RoleModel|null $existingRole
     *
     * @return array<string, mixed>
     */
    private function normalizePayload(array $data, ?RoleModel $existingRole = null): array
    {
        $payload = [];

        if (array_key_exists('tenant_id', $data)) {
            $tenantId = trim((string) $data['tenant_id']);
            $payload['tenant_id'] = $tenantId === '' ? null : $tenantId;
        } elseif ($existingRole !== null) {
            $payload['tenant_id'] = $existingRole->getTenantId();
        }

        if (array_key_exists('parent_role_id', $data)) {
            $parentRoleId = trim((string) $data['parent_role_id']);
            $payload['parent_role_id'] = $parentRoleId === '' ? null : $parentRoleId;
        }

        if (array_key_exists('name', $data)) {
            $payload['name'] = trim((string) $data['name']);
        }

        if (array_key_exists('description', $data)) {
            $description = trim((string) $data['description']);
            $payload['description'] = $description === '' ? null : $description;
        }

        return $payload;
    }
}
