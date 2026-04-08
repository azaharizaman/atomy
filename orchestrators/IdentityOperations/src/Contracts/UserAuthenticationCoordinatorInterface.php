<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\Contracts;

use Nexus\IdentityOperations\DTOs\UserContext;

/**
 * Coordinator interface for user authentication operations.
 */
interface UserAuthenticationCoordinatorInterface extends CoordinatorInterface
{
    /**
     * Authenticate a user with credentials.
     */
    public function authenticate(string $email, string $password, string $tenantId): UserContext;

    /**
     * Authenticate credentials for an MFA challenge without minting tokens/sessions.
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
     * Initiate OIDC SSO authentication.
     *
     * @return array{authorization_url: string, state: string}
     */
    public function initiateSso(string $tenantId, ?string $redirectUriOverride = null): array;

    /**
     * Handle OIDC SSO callback and return authenticated user context.
     */
    public function ssoCallback(string $tenantId, string $code, string $state): UserContext;
}
