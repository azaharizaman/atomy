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
     * Revoke a specific session for a tenant
     */
    public function revokeSessionForTenant(string $token, string $tenantId): void;

    /**
     * Revoke all sessions for a user
     */
    public function revokeAllSessions(string $userId): void;

    /**
     * Revoke all sessions for a user within a tenant
     */
    public function revokeAllSessionsForTenant(string $userId, string $tenantId): void;

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

    /**
     * Update session activity timestamp
     * 
     * @param string $sessionId Session identifier
     * @return void
     */
    public function updateActivity(string $sessionId): void;

    /**
     * Enforce maximum number of sessions for a user
     * Terminates oldest sessions if limit exceeded
     * 
     * @param string $userId User identifier
     * @param int $max Maximum allowed sessions
     * @return void
     */
    public function enforceMaxSessions(string $userId, int $max): void;

    /**
     * Terminate all sessions for a specific device fingerprint
     * 
     * @param string $userId User identifier
     * @param string $fingerprint Device fingerprint hash
     * @return void
     */
    public function terminateByDeviceId(string $userId, string $fingerprint): void;

    /**
     * Clean up inactive sessions based on last activity
     * 
     * @param int $inactivityThresholdDays Days of inactivity before cleanup
     * @return int Number of sessions cleaned up
     */
    public function cleanupInactiveSessions(int $inactivityThresholdDays = 7): int;
}
