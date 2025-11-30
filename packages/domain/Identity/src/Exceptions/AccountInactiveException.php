<?php

declare(strict_types=1);

namespace Nexus\Domain\Identity\Exceptions;

/**
 * Account inactive exception
 * 
 * Thrown when attempting to authenticate with an inactive account
 */
class AccountInactiveException extends \Exception
{
    public function __construct(string $status = 'inactive', int $code = 0, ?\Throwable $previous = null)
    {
        $message = "Account is not active: {$status}";
        parent::__construct($message, $code, $previous);
    }
}
