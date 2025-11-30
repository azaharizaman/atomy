<?php

declare(strict_types=1);

namespace Nexus\Tenant\Exceptions;

use Exception;

/**
 * Tenant Not Found Exception
 *
 * Thrown when a tenant cannot be found by the specified criteria.
 *
 * @package Nexus\Tenant\Exceptions
 */
class TenantNotFoundException extends Exception
{
    public function __construct(string $message = 'Tenant not found', int $code = 404, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public static function byId(string $id): self
    {
        return new self("Tenant with ID '{$id}' not found");
    }

    public static function byCode(string $code): self
    {
        return new self("Tenant with code '{$code}' not found");
    }

    public static function byDomain(string $domain): self
    {
        return new self("Tenant with domain '{$domain}' not found");
    }

    public static function bySubdomain(string $subdomain): self
    {
        return new self("Tenant with subdomain '{$subdomain}' not found");
    }
}
