<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\Rules;

/**
 * Interface for checking tenant access.
 */
interface TenantAccessCheckerInterface
{
    public function hasAccess(string $userId, string $tenantId): bool;

    public function getUserTenants(string $userId): array;
}
