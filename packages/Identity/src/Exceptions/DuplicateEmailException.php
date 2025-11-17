<?php

declare(strict_types=1);

namespace Nexus\Identity\Exceptions;

/**
 * Duplicate email exception
 * 
 * Thrown when attempting to create a user with an already-used email
 */
class DuplicateEmailException extends \Exception
{
    public function __construct(string $email, int $code = 0, ?\Throwable $previous = null)
    {
        $message = "Email address already in use: {$email}";
        parent::__construct($message, $code, $previous);
    }
}
