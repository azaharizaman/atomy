<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\Contracts;

use Nexus\IdentityOperations\DTOs\UserContext;

/**
 * Service interface for user authentication operations.
 */
interface UserAuthenticationServiceInterface
{
    /**
     * Authenticate a user with credentials.
     */
    public function authenticate(string $email, string $password, ?string $tenantId = null): UserContext;

    /**
     * Refresh authentication token.
     */
    public function refreshToken(string $refreshToken): UserContext;

    /**
     * Logout user and invalidate session.
     */
    public function logout(string $userId, ?string $sessionId = null): bool;

    /**
     * Validate current session.
     */
    public function validateSession(string $sessionId): bool;

    /**
     * Change user password.
     */
    public function changePassword(string $userId, string $currentPassword, string $newPassword): bool;

    /**
     * Reset user password (admin function).
     */
    public function resetPassword(string $userId, string $newPassword, string $resetBy): bool;

    /**
     * Invalidate all sessions for a user.
     */
    public function invalidateAllSessions(string $userId): bool;
}
