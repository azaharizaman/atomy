<?php

declare(strict_types=1);

namespace Nexus\Domain\Identity\Exceptions;

/**
 * Role in use exception
 * 
 * Thrown when attempting to delete a role that is assigned to users
 */
class RoleInUseException extends \Exception
{
    public function __construct(string $roleId, int $userCount, int $code = 0, ?\Throwable $previous = null)
    {
        $message = "Role {$roleId} cannot be deleted: assigned to {$userCount} user(s)";
        parent::__construct($message, $code, $previous);
    }
}
