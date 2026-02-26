<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\Services;

/**
 * Interface for assigning permissions.
 */
interface PermissionAssignerInterface
{
    public function assignPermission(string $userId, string $permission, string $tenantId, ?\DateTimeInterface $expiresAt = null): string;
}
