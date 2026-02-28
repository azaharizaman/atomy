<?php

declare(strict_types=1);

namespace Nexus\TenantOperations\Services;

/**
 * Interface for checking impersonation permissions.
 */
interface ImpersonationPermissionCheckerInterface
{
    public function hasPermission(string $adminUserId): bool;
}
