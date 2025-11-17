<?php

declare(strict_types=1);

namespace Nexus\Identity\Contracts;

use Nexus\Identity\ValueObjects\SessionToken;

/**
 * Session manager interface
 * 
 * Handles session lifecycle management
 */
interface SessionManagerInterface
{
    /**
     * Create a new session for a user
     * 
     * @param string $userId User identifier
     * @param array<string, mixed> $metadata Session metadata (IP, User-Agent, etc.)
     * @return SessionToken Generated session token
     */
    public function createSession(string $userId, array $metadata = []): SessionToken;

    /**
     * Validate a session token
     * 
     * @param string $token Session token
     * @return UserInterface Authenticated user
     * @throws \Nexus\Identity\Exceptions\InvalidSessionException
     */
    public function validateSession(string $token): UserInterface;

    /**
     * Check if a session token is valid
     */
    public function isValid(string $token): bool;

    /**
     * Revoke a specific session
     */
    public function revokeSession(string $token): void;

    /**
     * Revoke all sessions for a user
     */
    public function revokeAllSessions(string $userId): void;

    /**
     * Revoke all sessions except the current one
     */
    public function revokeOtherSessions(string $userId, string $currentToken): void;

    /**
     * Get all active sessions for a user
     * 
     * @return array<array<string, mixed>> Array of session data
     */
    public function getActiveSessions(string $userId): array;

    /**
     * Refresh a session (extend expiration)
     */
    public function refreshSession(string $token): SessionToken;

    /**
     * Clean up expired sessions
     */
    public function cleanupExpiredSessions(): int;
}
