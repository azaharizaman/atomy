<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\Services;

/**
 * Interface for assigning roles.
 */
interface RoleAssignerInterface
{
    public function assign(string $userId, string $roleId, string $tenantId): string;
}
