<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\Exceptions;

/**
 * Exception thrown when a user is not found.
 */
class UserNotFoundException extends IdentityOperationsException
{
    public static function forUser(string $userId): self
    {
        return new self("User '{$userId}' not found", ['user_id' => $userId]);
    }

    public static function forEmail(string $email): self
    {
        return new self("User with email '{$email}' not found", ['email' => $email]);
    }
}
