<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\DataProviders;

/**
 * Interface for querying permission data.
 */
interface PermissionQueryInterface
{
    /**
     * @return array<int, string>
     */
    public function getUserPermissions(string $userId, ?string $tenantId = null): array;

    /**
     * @return array<int, string>
     */
    public function getUserRoles(string $userId, ?string $tenantId = null): array;
}
