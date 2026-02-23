<?php

declare(strict_types=1);

namespace Nexus\TenantOperations\Exceptions;

/**
 * Exception for tenant validation errors.
 */
class TenantValidationException extends TenantOperationsException
{
    public static function tenantNotFound(string $tenantId): self
    {
        return new self("Tenant '{$tenantId}' not found", ['tenant_id' => $tenantId]);
    }

    public static function validationFailed(string $reason, array $errors = []): self
    {
        return new self("Tenant validation failed: {$reason}", ['reason' => $reason, 'errors' => $errors]);
    }
}
