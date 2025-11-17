<?php

declare(strict_types=1);

namespace Nexus\Identity\Exceptions;

/**
 * Invalid session exception
 * 
 * Thrown when a session token is invalid or expired
 */
class InvalidSessionException extends \Exception
{
    public function __construct(string $message = 'Invalid or expired session', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
