<?php

declare(strict_types=1);

namespace Nexus\SSO\Contracts;

use Nexus\SSO\ValueObjects\SsoSession;

/**
 * SSO session repository
 * 
 * Stores and retrieves active SSO sessions
 */
interface SsoSessionRepositoryInterface
{
    /**
     * Find SSO session by ID
     * 
     * @param string $sessionId Session identifier
     * @return SsoSession Active session
     * @throws \Nexus\SSO\Exceptions\SsoSessionExpiredException
     */
    public function findById(string $sessionId): SsoSession;

    /**
     * Save SSO session
     */
    public function save(SsoSession $session): void;

    /**
     * Delete SSO session
     */
    public function delete(string $sessionId): void;

    /**
     * Check if session exists and is valid
     */
    public function exists(string $sessionId): bool;

    /**
     * Clean up expired sessions
     * 
     * @return int Number of sessions deleted
     */
    public function cleanupExpiredSessions(): int;
}
