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

    public function authenticate(string $email, string $password, string $tenantId): UserContext
    {
        $this->logger->info('Authenticating user', [
            'email' => hash('sha256', $email),
            'tenant_id' => $tenantId,
        ]);

        $user = $this->authenticator->authenticate($email, $password, $tenantId);

        // Generate tokens
        $accessToken = $this->tokenManager->generateAccessToken($user['id'], $tenantId);
        $refreshToken = $this->tokenManager->generateRefreshToken($user['id'], $tenantId);
        $sessionId = $this->tokenManager->createSession($user['id'], $accessToken, $tenantId);

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

    public function refreshToken(string $refreshToken, string $tenantId): UserContext
    {
        $this->logger->info('Refreshing token');

        $payload = $this->tokenManager->validateRefreshToken($refreshToken, $tenantId);
        
        $user = $this->authenticator->getUserById($payload->userId);
        
        $accessToken = $this->tokenManager->generateAccessToken($user['id'], $payload->tenantId);

        return new UserContext(
            userId: $user['id'],
            email: $user['email'],
            firstName: $user['first_name'] ?? null,
            lastName: $user['last_name'] ?? null,
            tenantId: $payload->tenantId,
            status: $user['status'],
            permissions: $user['permissions'] ?? [],
            roles: $user['roles'] ?? [],
            accessToken: $accessToken,
        );
    }

    public function logout(string $userId, ?string $sessionId = null, ?string $tenantId = null): bool
    {
        try {
            if ($sessionId !== null) {
                $this->tokenManager->invalidateSession($sessionId, $tenantId ?? 'default');
            } else {
                $this->tokenManager->invalidateUserSessions($userId, $tenantId ?? 'default');
            }

            $this->auditLogger->log(
                'user.logged_out',
                $userId,
                ['session_id' => $sessionId, 'tenant_id' => $tenantId]
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

    public function invalidateAllSessions(string $userId, string $tenantId): bool
    {
        try {
            $this->tokenManager->invalidateUserSessions($userId, $tenantId);

            $this->auditLogger->log(
                'user.sessions_invalidated',
                $userId,
                ['tenant_id' => $tenantId]
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
