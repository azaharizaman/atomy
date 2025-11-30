<?php

declare(strict_types=1);

namespace Nexus\Identity\Exceptions;

/**
 * Password validation exception
 * 
 * Thrown when a password fails validation
 */
class PasswordValidationException extends \Exception
{
    /**
     * @param string[] $errors
     */
    public function __construct(array $errors, int $code = 0, ?\Throwable $previous = null)
    {
        $message = 'Password validation failed: ' . implode(', ', $errors);
        parent::__construct($message, $code, $previous);
    }
}
