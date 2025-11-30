<?php

declare(strict_types=1);

namespace Nexus\Identity\Services;

use Nexus\Identity\Contracts\UserInterface;
use Nexus\Identity\Contracts\UserRepositoryInterface;
use Nexus\Identity\Contracts\UserAuthenticatorInterface;
use Nexus\Identity\Contracts\SessionManagerInterface;
use Nexus\Identity\ValueObjects\Credentials;
use Nexus\Identity\ValueObjects\SessionToken;
use Nexus\Identity\Exceptions\InvalidCredentialsException;
use Nexus\Identity\Exceptions\UserNotFoundException;

/**
 * Authentication service
 * 
 * Handles user login and logout operations
 */
final readonly class AuthenticationService
{
    public function __construct(
        private UserAuthenticatorInterface $authenticator,
        private SessionManagerInterface $sessionManager,
        private UserRepositoryInterface $userRepository
    ) {
    }

    /**
     * Authenticate user with credentials and create session
     * 
     * @param Credentials $credentials User credentials
     * @param array<string, mixed> $metadata Session metadata (IP, User-Agent, etc.)
     * @return array{user: UserInterface, session: SessionToken}
     * @throws InvalidCredentialsException
     */
    public function login(Credentials $credentials, array $metadata = []): array
    {
        // Authenticate user
        $user = $this->authenticator->authenticate($credentials);

        // Update last login timestamp
        $this->userRepository->updateLastLogin($user->getId());

        // Create session
        $session = $this->sessionManager->createSession($user->getId(), $metadata);

        return [
            'user' => $user,
            'session' => $session,
        ];
    }

    /**
     * Logout user by revoking session
     */
    public function logout(string $sessionToken): void
    {
        $this->sessionManager->revokeSession($sessionToken);
    }

    /**
     * Logout user from all sessions
     */
    public function logoutAll(string $userId): void
    {
        $this->sessionManager->revokeAllSessions($userId);
    }

    /**
     * Validate a session and get authenticated user
     * 
     * @throws \Nexus\Identity\Exceptions\InvalidSessionException
     */
    public function validateSession(string $sessionToken): UserInterface
    {
        return $this->sessionManager->validateSession($sessionToken);
    }

    /**
     * Refresh a session
     */
    public function refreshSession(string $sessionToken): SessionToken
    {
        return $this->sessionManager->refreshSession($sessionToken);
    }

    /**
     * Get active sessions for a user
     * 
     * @return array<array<string, mixed>>
     */
    public function getActiveSessions(string $userId): array
    {
        return $this->sessionManager->getActiveSessions($userId);
    }
}
