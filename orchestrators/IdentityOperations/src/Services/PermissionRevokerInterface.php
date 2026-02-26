<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\Services;

/**
 * Interface for revoking permissions.
 */
interface PermissionRevokerInterface
{
    public function revokePermission(string $userId, string $permission, ?string $tenantId = null): void;
}
