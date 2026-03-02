<?php

declare(strict_types=1);

namespace Nexus\TenantOperations\Rules;

/**
 * Interface for checking impersonation permissions.
 */
interface ImpersonationPermissionCheckerInterface
{
    public function hasImpersonationPermission(string $adminUserId): bool;
}
