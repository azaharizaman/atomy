<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\Services;

/**
 * Interface for session management.
 */
interface SessionManagerInterface
{
    public function invalidateUserSessions(string $userId, string $tenantId): void;
    public function invalidateSession(string $sessionId, string $tenantId): void;
}
