<?php

declare(strict_types=1);

namespace Nexus\Tenant\Exceptions;

use Exception;

/**
 * Tenant Context Not Set Exception
 *
 * Thrown when tenant context is required but not set.
 *
 * @package Nexus\Tenant\Exceptions
 */
class TenantContextNotSetException extends Exception
{
    public function __construct(string $message = 'Tenant context is not set', int $code = 500, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public static function required(): self
    {
        return new self('Tenant context must be set before this operation');
    }
}
