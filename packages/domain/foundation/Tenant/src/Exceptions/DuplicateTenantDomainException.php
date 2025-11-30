<?php

declare(strict_types=1);

namespace Nexus\Tenant\Exceptions;

use Exception;

/**
 * Duplicate Tenant Domain Exception
 *
 * Thrown when attempting to assign a domain that is already in use.
 *
 * @package Nexus\Tenant\Exceptions
 */
class DuplicateTenantDomainException extends Exception
{
    public function __construct(string $message = 'Tenant domain already exists', int $code = 409, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public static function domain(string $domain): self
    {
        return new self("Domain '{$domain}' is already assigned to another tenant");
    }
}
