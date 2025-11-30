<?php

declare(strict_types=1);

namespace Nexus\Tenant\Exceptions;

use Exception;

/**
 * Impersonation Not Allowed Exception
 *
 * Thrown when impersonation is attempted without proper authorization.
 *
 * @package Nexus\Tenant\Exceptions
 */
class ImpersonationNotAllowedException extends Exception
{
    public function __construct(string $message = 'Impersonation is not allowed', int $code = 403, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public static function noPermission(string $userId): self
    {
        return new self("User '{$userId}' does not have permission to impersonate tenants");
    }
}
