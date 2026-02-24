<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\Services;

use Nexus\IdentityOperations\Contracts\UserAuthenticationServiceInterface;
use Nexus\IdentityOperations\DTOs\UserContext;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Service for user authentication operations.
 */
final readonly class UserAuthenticationService implements UserAuthenticationServiceInterface
{
    public function __construct(
        private AuthenticatorInterface $authenticator,
        private TokenManagerInterface $tokenManager,
        private PasswordChangerInterface $passwordChanger,
        private SessionValidatorInterface $sessionValidator,
        private AuditLoggerInterface $auditLogger,
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    public function authenticate(string $email, string $password, ?string $tenantId = null): UserContext
    {
        $this->logger->info('Authenticating user', [
            'email' => hash('sha256', $email),
            'tenant_id' => $tenantId,
        ]);

        $user = $this->authenticator->authenticate($email, $password, $tenantId);

        // Generate tokens
        $accessToken = $this->tokenManager->generateAccessToken($user['id'], $tenantId);
        $refreshToken = $this->tokenManager->generateRefreshToken($user['id']);
        $sessionId = $this->tokenManager->createSession($user['id'], $accessToken);

        // Log audit
        $this->auditLogger->log(
            'user.authenticated',
            $user['id'],
            ['email' => hash('sha256', $email), 'tenant_id' => $tenantId]
        );

        return new UserContext(
            userId: $user['id'],
            email: $user['email'],
            firstName: $user['first_name'] ?? null,
            lastName: $user['last_name'] ?? null,
            tenantId: $tenantId,
            status: $user['status'],
            permissions: $user['permissions'] ?? [],
            roles: $user['roles'] ?? [],
            sessionId: $sessionId,
            accessToken: $accessToken,
            refreshToken: $refreshToken,
        );
    }

    public function refreshToken(string $refreshToken): UserContext
    {
        $this->logger->info('Refreshing token');

        $tokenData = $this->tokenManager->validateRefreshToken($refreshToken);
        
        $user = $this->authenticator->getUserById($tokenData['user_id']);
        
        $accessToken = $this->tokenManager->generateAccessToken($user['id'], $tokenData['tenant_id'] ?? null);

        return new UserContext(
            userId: $user['id'],
            email: $user['email'],
            firstName: $user['first_name'] ?? null,
            lastName: $user['last_name'] ?? null,
            tenantId: $tokenData['tenant_id'] ?? null,
            status: $user['status'],
            permissions: $user['permissions'] ?? [],
            roles: $user['roles'] ?? [],
            accessToken: $accessToken,
        );
    }

    public function logout(string $userId, ?string $sessionId = null): bool
    {
        try {
            if ($sessionId !== null) {
                $this->tokenManager->invalidateSession($sessionId);
            } else {
                $this->tokenManager->invalidateUserSessions($userId);
            }

            $this->auditLogger->log(
                'user.logged_out',
                $userId,
                ['session_id' => $sessionId]
            );

            return true;
        } catch (\Throwable $e) {
            $this->logger->error('Failed to logout user', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function validateSession(string $sessionId): bool
    {
        return $this->sessionValidator->isValid($sessionId);
    }

    public function changePassword(string $userId, string $currentPassword, string $newPassword): bool
    {
        try {
            $this->passwordChanger->changeWithVerification($userId, $currentPassword, $newPassword);

            $this->auditLogger->log(
                'user.password_changed',
                $userId,
                []
            );

            return true;
        } catch (\Throwable $e) {
            $this->logger->error('Failed to change password', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function resetPassword(string $userId, string $newPassword, string $resetBy): bool
    {
        try {
            $this->passwordChanger->resetByAdmin($userId, $newPassword);

            $this->auditLogger->log(
                'user.password_reset',
                $userId,
                ['reset_by' => $resetBy]
            );

            return true;
        } catch (\Throwable $e) {
            $this->logger->error('Failed to reset password', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function invalidateAllSessions(string $userId): bool
    {
        try {
            $this->tokenManager->invalidateUserSessions($userId);

            $this->auditLogger->log(
                'user.sessions_invalidated',
                $userId,
                []
            );

            return true;
        } catch (\Throwable $e) {
            $this->logger->error('Failed to invalidate sessions', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}

/**
 * Interface for authentication.
 */
interface AuthenticatorInterface
{
    /**
     * @return array{id: string, email: string, first_name: string|null, last_name: string|null, status: string, permissions: array, roles: array}
     */
    public function authenticate(string $email, string $password, ?string $tenantId = null): array;

    /**
     * @return array{id: string, email: string, first_name: string|null, last_name: string|null, status: string, permissions: array, roles: array}
     */
    public function getUserById(string $userId): array;
}

/**
 * Interface for token management.
 */
interface TokenManagerInterface
{
    public function generateAccessToken(string $userId, ?string $tenantId = null): string;
    public function generateRefreshToken(string $userId): string;
    public function validateRefreshToken(string $refreshToken): array;
    public function createSession(string $userId, string $accessToken): string;
    public function invalidateSession(string $sessionId): void;
    public function invalidateUserSessions(string $userId): void;
}

/**
 * Interface for password changing.
 */
interface PasswordChangerInterface
{
    public function changeWithVerification(string $userId, string $currentPassword, string $newPassword): void;
    public function resetByAdmin(string $userId, string $newPassword): void;
}

/**
 * Interface for session validation.
 */
interface SessionValidatorInterface
{
    public function isValid(string $sessionId): bool;
}
