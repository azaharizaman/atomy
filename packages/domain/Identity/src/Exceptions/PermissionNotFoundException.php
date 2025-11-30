<?php

declare(strict_types=1);

namespace Nexus\Domain\Identity\Exceptions;

/**
 * Permission not found exception
 * 
 * Thrown when a requested permission cannot be found
 */
class PermissionNotFoundException extends \Exception
{
    public function __construct(string $identifier, int $code = 0, ?\Throwable $previous = null)
    {
        $message = "Permission not found: {$identifier}";
        parent::__construct($message, $code, $previous);
    }
}
