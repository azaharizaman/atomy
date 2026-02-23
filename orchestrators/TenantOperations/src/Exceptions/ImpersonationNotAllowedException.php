<?php

declare(strict_types=1);

namespace Nexus\TenantOperations\Exceptions;

/**
 * Exception thrown when impersonation is not allowed.
 */
class ImpersonationNotAllowedException extends TenantImpersonationException
{
    public static function forAdmin(string $adminUserId): self
    {
        return new self(
            "Admin '{$adminUserId}' does not have permission to impersonate tenants",
            ['admin_user_id' => $adminUserId]
        );
    }

    public static function tenantSuspended(string $tenantId): self
    {
        return new self(
            "Cannot impersonate suspended tenant '{$tenantId}'",
            ['tenant_id' => $tenantId]
        );
    }
}
