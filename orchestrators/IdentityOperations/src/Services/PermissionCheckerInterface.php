<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\Services;

/**
 * Interface for checking permissions.
 */
interface PermissionCheckerInterface
{
    public function check(string $userId, string $permission, ?string $tenantId = null): bool;
    public function getAll(string $userId, ?string $tenantId = null): array;
    public function getRoles(string $userId, ?string $tenantId = null): array;
}
