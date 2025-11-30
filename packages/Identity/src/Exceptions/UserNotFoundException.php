<?php

declare(strict_types=1);

namespace Nexus\Identity\Exceptions;

/**
 * User not found exception
 * 
 * Thrown when a requested user cannot be found
 */
class UserNotFoundException extends \Exception
{
    public function __construct(string $identifier, int $code = 0, ?\Throwable $previous = null)
    {
        $message = "User not found: {$identifier}";
        parent::__construct($message, $code, $previous);
    }
}
