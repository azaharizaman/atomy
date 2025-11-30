<?php

declare(strict_types=1);

namespace Nexus\Domain\Identity\Exceptions;

/**
 * Role not found exception
 * 
 * Thrown when a requested role cannot be found
 */
class RoleNotFoundException extends \Exception
{
    public function __construct(string $identifier, int $code = 0, ?\Throwable $previous = null)
    {
        $message = "Role not found: {$identifier}";
        parent::__construct($message, $code, $previous);
    }
}
