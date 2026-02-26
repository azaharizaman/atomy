<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\Rules;

/**
 * Interface for validating permissions.
 */
interface PermissionValidatorInterface
{
    public function permissionExists(string $permission): bool;

    public function userHasPermission(string $userId, string $permission, ?string $tenantId = null): bool;
}
