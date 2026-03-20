<?php

declare(strict_types=1);

namespace App\Services\Identity;

use Nexus\Identity\Contracts\PermissionInterface;
use Nexus\Identity\Contracts\PermissionQueryInterface;
use Nexus\Identity\Exceptions\PermissionNotFoundException;

/**
 * Atomy-Q has no standalone permission catalog; RBAC tables are not wired yet.
 */
final readonly class AtomyPermissionQueryStub implements PermissionQueryInterface
{
    public function findById(string $id): PermissionInterface
    {
        throw new PermissionNotFoundException($id);
    }

    public function findByName(string $name): PermissionInterface
    {
        throw new PermissionNotFoundException($name);
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
}
