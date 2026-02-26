<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\Services;

use Nexus\IdentityOperations\DTOs\PermissionDto;
use Nexus\IdentityOperations\DTOs\RoleDto;

/**
 * Interface for checking permissions.
 */
interface PermissionCheckerInterface
{
    public function check(string $userId, string $permission, ?string $tenantId = null): bool;

    /**
     * @return list<PermissionDto>
     */
    public function getAll(string $userId, ?string $tenantId = null): array;

    /**
     * @return list<RoleDto>
     */
    public function getRoles(string $userId, ?string $tenantId = null): array;
}
