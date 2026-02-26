<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\Services;

/**
 * Interface for revoking roles.
 */
interface RoleRevokerInterface
{
    public function revoke(string $userId, string $roleId, string $tenantId): void;
}
