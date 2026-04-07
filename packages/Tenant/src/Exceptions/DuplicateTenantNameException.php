<?php

declare(strict_types=1);

namespace Nexus\Tenant\Exceptions;

use Exception;

/**
 * Duplicate Tenant Name Exception
 */
class DuplicateTenantNameException extends Exception
{
    public function __construct(string $message = 'Tenant name already exists', int $code = 409, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public static function name(string $name): self
    {
        return new self("Tenant name '{$name}' is already in use");
    }
}
