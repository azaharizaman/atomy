<?php

declare(strict_types=1);

namespace Nexus\Laravel\Identity\Adapters;

use App\Models\Permission as PermissionModel;
use Nexus\Identity\Contracts\PermissionInterface;
use Nexus\Identity\Contracts\PermissionRepositoryInterface;

final readonly class EloquentPermissionRepository implements PermissionRepositoryInterface
{
    public function findById(string $id): PermissionInterface
    {
        throw new \BadMethodCallException('Not implemented');
    }

    public function findByName(string $name): PermissionInterface
    {
        throw new \BadMethodCallException('Not implemented');
    }

    public function findByNameOrNull(string $name): ?PermissionInterface
    {
        return null;
    }

    public function nameExists(string $name, ?string $excludePermissionId = null): bool
    {
        return false;
    }

    public function getAll(): array
    {
        return [];
    }

    public function findByResource(string $resource): array
    {
        return [];
    }

    public function findMatching(string $permissionName): array
    {
        return [];
    }

    public function create(array $data): PermissionInterface
    {
        throw new \BadMethodCallException('Not implemented');
    }

    public function update(string $id, array $data): PermissionInterface
    {
        throw new \BadMethodCallException('Not implemented');
    }

    public function delete(string $id): bool
    {
        return false;
    }
}
