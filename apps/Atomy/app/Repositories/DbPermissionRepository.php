<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Permission;
use Nexus\Identity\Contracts\PermissionInterface;
use Nexus\Identity\Contracts\PermissionRepositoryInterface;
use Nexus\Identity\Exceptions\PermissionNotFoundException;

/**
 * Database permission repository implementation
 */
final readonly class DbPermissionRepository implements PermissionRepositoryInterface
{
    public function findById(string $id): PermissionInterface
    {
        $permission = Permission::find($id);

        if (!$permission) {
            throw new PermissionNotFoundException($id);
        }

        return $permission;
    }

    public function findByName(string $name): PermissionInterface
    {
        $permission = Permission::where('name', $name)->first();

        if (!$permission) {
            throw new PermissionNotFoundException($name);
        }

        return $permission;
    }

    public function findByNameOrNull(string $name): ?PermissionInterface
    {
        return Permission::where('name', $name)->first();
    }

    public function create(array $data): PermissionInterface
    {
        return Permission::create($data);
    }

    public function update(string $id, array $data): PermissionInterface
    {
        $permission = Permission::findOrFail($id);
        $permission->update($data);
        return $permission->fresh();
    }

    public function delete(string $id): bool
    {
        $permission = Permission::findOrFail($id);
        return (bool) $permission->delete();
    }

    public function nameExists(string $name, ?string $excludePermissionId = null): bool
    {
        $query = Permission::where('name', $name);

        if ($excludePermissionId) {
            $query->where('id', '!=', $excludePermissionId);
        }

        return $query->exists();
    }

    public function getAll(): array
    {
        return Permission::all()->all();
    }

    public function findByResource(string $resource): array
    {
        return Permission::where('resource', $resource)->get()->all();
    }

    public function findMatching(string $permissionName): array
    {
        $parts = explode('.', $permissionName, 2);

        if (count($parts) !== 2) {
            return [];
        }

        [$resource, $action] = $parts;

        return Permission::where('resource', $resource)
            ->where(function ($query) use ($action) {
                $query->where('action', $action)
                    ->orWhere('action', '*');
            })
            ->get()
            ->all();
    }
}
