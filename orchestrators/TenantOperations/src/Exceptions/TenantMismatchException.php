<?php

declare(strict_types=1);

namespace Nexus\TenantOperations\Exceptions;

/**
 * Exception thrown when requested tenant ID does not match current context.
 */
final class TenantMismatchException extends TenantOperationsException
{
    public static function forTenant(string $requestedId, ?string $contextId): self
    {
        return new self(sprintf(
            'Tenant context mismatch: requested "%s", but current context is "%s"',
            $requestedId,
            $contextId ?? 'none'
        ));
    }
}
