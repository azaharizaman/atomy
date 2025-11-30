<?php

declare(strict_types=1);

namespace Nexus\Tenant\Exceptions;

use Exception;

/**
 * Tenant Suspended Exception
 *
 * Thrown when attempting to access a suspended tenant.
 *
 * @package Nexus\Tenant\Exceptions
 */
class TenantSuspendedException extends Exception
{
    public function __construct(string $message = 'Tenant is suspended', int $code = 403, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public static function cannotAccess(string $tenantId): self
    {
        return new self("Cannot access suspended tenant: {$tenantId}");
    }
}
