<?php

declare(strict_types=1);

namespace Nexus\TenantOperations\Exceptions;

/**
 * Exception for tenant lifecycle errors.
 */
class TenantLifecycleException extends TenantOperationsException
{
    public static function alreadySuspended(string $tenantId): self
    {
        return new self("Tenant '{$tenantId}' is already suspended", ['tenant_id' => $tenantId]);
    }

    public static function alreadyActive(string $tenantId): self
    {
        return new self("Tenant '{$tenantId}' is already active", ['tenant_id' => $tenantId]);
    }

    public static function alreadyArchived(string $tenantId): self
    {
        return new self("Tenant '{$tenantId}' is already archived", ['tenant_id' => $tenantId]);
    }

    public static function transitionNotAllowed(string $tenantId, string $fromStatus, string $toStatus): self
    {
        return new self(
            "Cannot transition tenant from '{$fromStatus}' to '{$toStatus}'",
            ['tenant_id' => $tenantId, 'from' => $fromStatus, 'to' => $toStatus]
        );
    }

    public static function deleteFailed(string $reason): self
    {
        return new self("Failed to delete tenant: {$reason}", ['reason' => $reason]);
    }
}
