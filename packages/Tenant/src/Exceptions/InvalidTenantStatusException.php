<?php

declare(strict_types=1);

namespace Nexus\Tenant\Exceptions;

use Exception;

/**
 * Invalid Tenant Status Exception
 *
 * Thrown when an invalid tenant status value is provided.
 *
 * @package Nexus\Tenant\Exceptions
 */
class InvalidTenantStatusException extends Exception
{
    public function __construct(string $message = 'Invalid tenant status', int $code = 400, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public static function invalidStatus(string $status): self
    {
        return new self("Invalid tenant status: '{$status}'. Valid statuses are: pending, active, suspended, archived, trial");
    }
}
