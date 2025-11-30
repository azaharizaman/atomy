<?php

declare(strict_types=1);

namespace Nexus\Domain\Identity\Exceptions;

use RuntimeException;

/**
 * Unauthorized Exception
 *
 * Thrown when a user attempts an operation they are not authorized to perform.
 *
 * @package Nexus\Domain\Identity
 */
final class UnauthorizedException extends RuntimeException
{
    /**
     * User lacks required permission
     *
     * @param string $userId User identifier
     * @param string $permission Required permission
     * @return self
     */
    public static function missingPermission(string $userId, string $permission): self
    {
        return new self("User {$userId} lacks required permission: {$permission}");
    }

    /**
     * Generic unauthorized access
     *
     * @param string $message Error message
     * @return self
     */
    public static function accessDenied(string $message): self
    {
        return new self("Access denied: {$message}");
    }
}
