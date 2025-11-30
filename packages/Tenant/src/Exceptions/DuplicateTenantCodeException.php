<?php

declare(strict_types=1);

namespace Nexus\Tenant\Exceptions;

use Exception;

/**
 * Duplicate Tenant Code Exception
 *
 * Thrown when attempting to create a tenant with a code that already exists.
 *
 * @package Nexus\Tenant\Exceptions
 */
class DuplicateTenantCodeException extends Exception
{
    public function __construct(string $message = 'Tenant code already exists', int $code = 409, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public static function code(string $code): self
    {
        return new self("Tenant code '{$code}' is already in use");
    }
}
