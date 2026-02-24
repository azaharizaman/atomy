<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\Exceptions;

/**
 * Exception thrown for user authentication errors.
 */
class UserAuthenticationException extends IdentityOperationsException
{
    public static function invalidCredentials(): self
    {
        return new self('Invalid email or password');
    }

    public static function accountLocked(string $userId): self
    {
        return new self("Account '{$userId}' is locked", ['user_id' => $userId]);
    }

    public static function accountSuspended(string $userId): self
    {
        return new self("Account '{$userId}' is suspended", ['user_id' => $userId]);
    }

    public static function accountDeactivated(string $userId): self
    {
        return new self("Account '{$userId}' is deactivated", ['user_id' => $userId]);
    }

    public static function sessionExpired(string $sessionId): self
    {
        return new self('Session expired', ['session_id' => $sessionId]);
    }

    public static function invalidSession(): self
    {
        return new self('Invalid session');
    }

    public static function passwordMismatch(): self
    {
        return new self('Current password does not match');
    }
}
