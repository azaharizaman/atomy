<?php

declare(strict_types=1);

namespace Nexus\Identity\Exceptions;

/**
 * Invalid token exception
 * 
 * Thrown when an API token is invalid or expired
 */
class InvalidTokenException extends \Exception
{
    public function __construct(string $message = 'Invalid or expired token', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
