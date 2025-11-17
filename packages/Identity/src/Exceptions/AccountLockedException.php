<?php

declare(strict_types=1);

namespace Nexus\Identity\Exceptions;

/**
 * Account locked exception
 * 
 * Thrown when attempting to authenticate with a locked account
 */
class AccountLockedException extends \Exception
{
    public function __construct(string $reason = 'Account is locked', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($reason, $code, $previous);
    }
}
