<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\Services;

/**
 * Interface for assigning permissions.
 */
interface PermissionAssignerInterface
{
    public function assign(string $userId, string $permission, ?string $tenantId = null, ?string $expiresAt = null): string;
}
