<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\Coordinators;

use Nexus\IdentityOperations\Contracts\UserAuthenticationCoordinatorInterface;
use Nexus\IdentityOperations\Contracts\UserAuthenticationServiceInterface;
use Nexus\IdentityOperations\Contracts\UserContextProviderInterface;
use Nexus\IdentityOperations\DTOs\UserContext;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Coordinator for user authentication operations.
 */
final readonly class UserAuthenticationCoordinator implements UserAuthenticationCoordinatorInterface
{
    public function __construct(
        private UserAuthenticationServiceInterface $authService,
        private UserContextProviderInterface $contextDataProvider,
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    public function getName(): string
    {
        return 'UserAuthenticationCoordinator';
    }

    public function hasRequiredData(string $userId): bool
    {
        return $this->contextDataProvider->userExists($userId);
    }

    public function authenticate(string $email, string $password, ?string $tenantId = null): UserContext
    {
        $this->logger->info('Processing authentication', [
            'email' => hash('sha256', $email),
            'tenant_id' => $tenantId,
        ]);

        return $this->authService->authenticate($email, $password, $tenantId);
    }

    public function refreshToken(string $refreshToken): UserContext
    {
        $this->logger->info('Processing token refresh');

        return $this->authService->refreshToken($refreshToken);
    }

    public function logout(string $userId, ?string $sessionId = null): bool
    {
        $this->logger->info('Processing logout', [
            'user_id' => $userId,
            'session_id' => $sessionId,
        ]);

        return $this->authService->logout($userId, $sessionId);
    }

    public function validateSession(string $sessionId): bool
    {
        return $this->authService->validateSession($sessionId);
    }

    public function changePassword(string $userId, string $currentPassword, string $newPassword): bool
    {
        $this->logger->info('Processing password change', [
            'user_id' => $userId,
        ]);

        return $this->authService->changePassword($userId, $currentPassword, $newPassword);
    }

    public function resetPassword(string $userId, string $newPassword, string $resetBy): bool
    {
        $this->logger->info('Processing password reset', [
            'user_id' => $userId,
            'reset_by' => $resetBy,
        ]);

        return $this->authService->resetPassword($userId, $newPassword, $resetBy);
    }
}
