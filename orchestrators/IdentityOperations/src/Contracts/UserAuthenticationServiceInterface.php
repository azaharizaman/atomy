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
    public function authenticate(string $email, string $password, string $tenantId): UserContext;

    /**
     * Authenticate credentials for an MFA challenge without minting tokens/sessions.
     *
     * This is used by the application to bridge alpha's `challenge_id` flow to the
     * existing MFA verification service.
     */
    public function authenticateForMfaChallenge(string $email, string $password, string $tenantId): UserContext;

    /**
     * Complete an MFA login after verification, minting tokens and creating a session.
     */
    public function completeMfaLogin(string $userId, string $tenantId): UserContext;

    /**
     * Refresh authentication token.
     */
    public function refreshToken(string $refreshToken, string $tenantId): UserContext;

    /**
     * Logout user and invalidate session.
     */
    public function logout(string $userId, ?string $sessionId, string $tenantId): bool;

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
    public function invalidateAllSessions(string $userId, string $tenantId): bool;
}
