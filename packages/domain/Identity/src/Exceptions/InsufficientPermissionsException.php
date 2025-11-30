<?php

declare(strict_types=1);

namespace Nexus\Domain\Identity\Exceptions;

/**
 * Insufficient permissions exception
 * 
 * Thrown when a user lacks required permissions
 */
class InsufficientPermissionsException extends \Exception
{
    public function __construct(string $permission, int $code = 0, ?\Throwable $previous = null)
    {
        $message = "Insufficient permissions: {$permission} required";
        parent::__construct($message, $code, $previous);
    }
}
