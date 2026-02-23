<?php

declare(strict_types=1);

namespace Nexus\TenantOperations\Exceptions;

/**
 * Exception thrown when tenant is not active.
 */
class TenantNotActiveException extends TenantValidationException
{
    public static function forTenant(string $tenantId, ?string $status = null): self
    {
        $message = $status !== null 
            ? "Tenant '{$tenantId}' is not active (status: {$status})"
            : "Tenant '{$tenantId}' is not active";
            
        return new self($message, ['tenant_id' => $tenantId, 'status' => $status]);
    }
}
