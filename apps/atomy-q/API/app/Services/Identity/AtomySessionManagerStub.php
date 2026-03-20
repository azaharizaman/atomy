<?php

declare(strict_types=1);

namespace App\Services\Identity;

use Nexus\Identity\Contracts\SessionManagerInterface;
use Nexus\Identity\Contracts\UserInterface;
use Nexus\Identity\Exceptions\InvalidSessionException;
use Nexus\Identity\ValueObjects\SessionToken;

/**
 * Session persistence is not wired for Atomy-Q API (JWT-only). Stub satisfies Nexus Identity container graph.
 */
final class AtomySessionManagerStub implements SessionManagerInterface
{
    public function createSession(string $userId, array $metadata = []): SessionToken
    {
        return new SessionToken(
            token: bin2hex(random_bytes(16)),
            userId: $userId,
            expiresAt: (new \DateTimeImmutable())->modify('+1 hour'),
            metadata: $metadata,
        );
    }

    public function validateSession(string $token): UserInterface
    {
        throw new InvalidSessionException('Session validation is not enabled for Atomy-Q');
    }

    public function isValid(string $token): bool
    {
        return false;
    }

    public function revokeSession(string $token): void
    {
    }

    public function revokeSessionForTenant(string $token, string $tenantId): void
    {
    }

    public function revokeAllSessions(string $userId): void
    {
    }

    public function revokeAllSessionsForTenant(string $userId, string $tenantId): void
    {
    }

    public function revokeOtherSessions(string $userId, string $currentToken): void
    {
    }

    public function getActiveSessions(string $userId): array
    {
        return [];
    }

    public function refreshSession(string $token): SessionToken
    {
        throw new InvalidSessionException('Session refresh is not enabled for Atomy-Q');
    }

    public function cleanupExpiredSessions(): int
    {
        return 0;
    }

    public function updateActivity(string $sessionId): void
    {
    }

    public function enforceMaxSessions(string $userId, int $max): void
    {
    }

    public function terminateByDeviceId(string $userId, string $fingerprint): void
    {
    }

    public function cleanupInactiveSessions(int $inactivityThresholdDays = 7): int
    {
        return 0;
    }
}
