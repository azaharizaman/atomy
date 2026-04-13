<?php

declare(strict_types=1);

namespace Nexus\Laravel\Identity\Repositories;

use Nexus\Identity\Contracts\PermissionInterface;
use Nexus\Identity\Contracts\PermissionRepositoryInterface;
use Nexus\Identity\Exceptions\PermissionNotFoundException;
use App\Models\Permission as PermissionModel;

final readonly class EloquentPermissionRepository implements PermissionRepositoryInterface
{
    public function findById(string $id): PermissionInterface
    {
        $permission = PermissionModel::query()->whereKey($id)->first();
        if ($permission === null) {
            throw new PermissionNotFoundException($id);
        }

        return $permission;
    }

    public function findByName(string $name): PermissionInterface
    {
        $permission = $this->findByNameOrNull($name);
        if ($permission === null) {
            throw new PermissionNotFoundException($name);
        }

        return $permission;
    }

    public function findByNameOrNull(string $name): ?PermissionInterface
    {
        $permission = PermissionModel::query()
            ->where('name', trim($name))
            ->first();

        return $permission instanceof PermissionModel ? $permission : null;
    }

    public function nameExists(string $name, ?string $excludePermissionId = null): bool
    {
        $query = PermissionModel::query()->where('name', trim($name));

        if ($excludePermissionId !== null && trim($excludePermissionId) !== '') {
            $query->whereKeyNot($excludePermissionId);
        }

        return $query->exists();
    }

    /**
     * @return PermissionInterface[]
     */
    public function getAll(): array
    {
        return PermissionModel::query()
            ->orderBy('name')
            ->get()
            ->all();
    }

    /**
     * @return PermissionInterface[]
     */
    public function findByResource(string $resource): array
    {
        $normalizedResource = trim($resource);
        if ($normalizedResource === '') {
            return [];
        }

        return PermissionModel::query()
            ->where('name', 'like', $normalizedResource . '.%')
            ->orWhere('name', $normalizedResource)
            ->orderBy('name')
            ->get()
            ->all();
    }

    /**
     * @return PermissionInterface[]
     */
    public function findMatching(string $permissionName): array
    {
        $normalizedPermission = trim($permissionName);
        if ($normalizedPermission === '') {
            return [];
        }

        $segments = explode('.', $normalizedPermission);
        $resource = trim((string) ($segments[0] ?? ''));

        $query = PermissionModel::query()->orderBy('name');
        if ($resource !== '') {
            $query->where(function ($builder) use ($normalizedPermission, $resource): void {
                $builder
                    ->where('name', '*')
                    ->orWhere('name', $resource)
                    ->orWhere('name', $normalizedPermission)
                    ->orWhere('name', $resource . '.*')
                    ->orWhere('name', 'like', $resource . '.%');
            });
        }

        return $query
            ->get()
            ->filter(static fn (PermissionModel $permission): bool => $permission->matches($normalizedPermission))
            ->values()
            ->all();
    }

    public function create(array $data): PermissionInterface
    {
        if (! isset($data['name']) || ! is_string($data['name']) || trim($data['name']) === '') {
            throw new \InvalidArgumentException('Permission name is required');
        }

        return PermissionModel::query()->create($this->normalizePayload($data));
    }

    public function update(string $id, array $data): PermissionInterface
    {
        $permission = PermissionModel::query()->whereKey($id)->first();
        if ($permission === null) {
            throw new PermissionNotFoundException($id);
        }

        $permission->fill($this->normalizePayload($data, $permission));
        $permission->save();

        return $permission->fresh() ?? $permission;
    }

    public function delete(string $id): bool
    {
        return PermissionModel::query()->whereKey($id)->delete() > 0;
    }

    /**
     * @param array<string, mixed> $data
     * @param PermissionModel|null $existingPermission
     *
     * @return array<string, mixed>
     */
    private function normalizePayload(array $data, ?PermissionModel $existingPermission = null): array
    {
        $payload = [];

        if (array_key_exists('name', $data)) {
            $normalizedName = trim((string) $data['name']);
            if ($normalizedName !== '') {
                $payload['name'] = $normalizedName;
            } elseif ($existingPermission !== null) {
                $payload['name'] = $existingPermission->getName();
            }
        } elseif ($existingPermission !== null) {
            $payload['name'] = $existingPermission->getName();
        }

        if (array_key_exists('description', $data)) {
            $description = trim((string) $data['description']);
            $payload['description'] = $description === '' ? null : $description;
        }

        return $payload;
    }
}
