<?php

declare(strict_types=1);

namespace Nexus\TenantOperations\Exceptions;

/**
 * Exception for tenant impersonation errors.
 */
class TenantImpersonationException extends TenantOperationsException
{
    public static function notAllowed(string $adminUserId): self
    {
        return new self(
            "Admin '{$adminUserId}' does not have permission to impersonate tenants",
            ['admin_user_id' => $adminUserId]
        );
    }

    public static function sessionAlreadyActive(string $adminUserId): self
    {
        return new self(
            "Admin '{$adminUserId}' already has an active impersonation session",
            ['admin_user_id' => $adminUserId]
        );
    }

    public static function sessionNotFound(string $adminUserId): self
    {
        return new self(
            "No active impersonation session found for admin '{$adminUserId}'",
            ['admin_user_id' => $adminUserId]
        );
    }

    public static function sessionExpired(string $sessionId): self
    {
        return new self(
            "Impersonation session '{$sessionId}' has expired",
            ['session_id' => $sessionId]
        );
    }
}
