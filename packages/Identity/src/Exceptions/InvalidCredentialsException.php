<?php

declare(strict_types=1);

namespace Nexus\Identity\Exceptions;

/**
 * Invalid credentials exception
 * 
 * Thrown when authentication fails due to invalid credentials
 */
class InvalidCredentialsException extends \Exception
{
    public function __construct(string $message = 'Invalid credentials', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
