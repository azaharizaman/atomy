<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\Services;

/**
 * Interface for tenant user assignment.
 */
interface TenantUserAssignerInterface
{
    public function assignTenantRoles(string $userId, string $tenantId, array $roles): string;
}
