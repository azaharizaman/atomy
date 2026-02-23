<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\Exceptions;

/**
 * Exception thrown when trying to create a user that already exists.
 */
class UserAlreadyExistsException extends IdentityOperationsException
{
    public static function forEmail(string $email): self
    {
        return new self("User with email '{$email}' already exists", ['email' => $email]);
    }

    public static function forUserInTenant(string $userId, string $tenantId): self
    {
        return new self(
            "User '{$userId}' already exists in tenant '{$tenantId}'",
            ['user_id' => $userId, 'tenant_id' => $tenantId]
        );
    }
}
