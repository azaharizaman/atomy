<?php

declare(strict_types=1);

namespace App\Services\Identity;

use Nexus\Identity\Contracts\RoleInterface;
use Nexus\Identity\Contracts\RoleQueryInterface;
use Nexus\Identity\Exceptions\RoleNotFoundException;

/**
 * Atomy-Q stores a single `role` string on users; no role master table yet.
 */
final readonly class AtomyRoleQueryStub implements RoleQueryInterface
{
    public function findById(string $id): RoleInterface
    {
        throw new RoleNotFoundException($id);
    }

    public function findByName(string $name, ?string $tenantId = null): RoleInterface
    {
        throw new RoleNotFoundException($name);
    }

    public function findByNameOrNull(string $name, ?string $tenantId = null): ?RoleInterface
    {
        return null;
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
}
