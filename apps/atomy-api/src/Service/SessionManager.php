<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Session;
use App\Repository\SessionRepository;
use App\Repository\UserRepository;
use Nexus\Identity\Contracts\SessionManagerInterface;
use Nexus\Identity\Contracts\UserInterface;
use Nexus\Identity\Exceptions\InvalidSessionException;
use Nexus\Identity\ValueObjects\SessionToken;
use Symfony\Component\Uid\Ulid;

final readonly class SessionManager implements SessionManagerInterface
{
    private const SESSION_TTL_SECONDS = 3600 * 24; // 24 hours default

    public function __construct(
        private SessionRepository $sessionRepository,
        private UserRepository $userRepository,
    ) {}

    /**
     * @param array<string, mixed> $metadata
     */
    public function createSession(string $userId, array $metadata = []): SessionToken
    {
        $token = bin2hex(random_bytes(32));
        $expiresAt = new \DateTimeImmutable('+' . self::SESSION_TTL_SECONDS . ' seconds');
        $deviceFingerprint = $metadata['device_fingerprint'] ?? null;

        $session = new Session(
            id: (string) new Ulid(),
            token: $token,
            userId: $userId,
            expiresAt: $expiresAt,
            metadata: $metadata,
            deviceFingerprint: $deviceFingerprint,
        );

        $em = $this->sessionRepository->getEntityManager();
        $em->persist($session);
        $em->flush();

        return new SessionToken(
            token: $token,
            userId: $userId,
            expiresAt: $expiresAt,
            metadata: $metadata,
            deviceFingerprint: $deviceFingerprint,
            lastActivityAt: $session->getLastActivityAt(),
        );
    }

    public function validateSession(string $token): UserInterface
    {
        $session = $this->sessionRepository->findByToken($token);

        if ($session === null) {
            throw new InvalidSessionException('Session not found');
        }

        if (!$session->isValid()) {
            throw new InvalidSessionException('Session is no longer valid');
        }

        // Update activity
        $session->updateActivity();
        $this->sessionRepository->getEntityManager()->flush();

        return $this->userRepository->findById($session->getUserId());
    }

    public function isValid(string $token): bool
    {
        try {
            $this->validateSession($token);
            return true;
        } catch (InvalidSessionException) {
            return false;
        }
    }

    public function revokeSession(string $token): void
    {
        $session = $this->sessionRepository->findByToken($token);
        if ($session !== null) {
            $session->revoke();
            $this->sessionRepository->getEntityManager()->flush();
        }
    }

    public function revokeSessionForTenant(string $token, string $tenantId): void
    {
        $session = $this->sessionRepository->findByToken($token);
        if ($session !== null) {
            $metadata = $session->getMetadata();
            if (isset($metadata['tenant_id']) && $metadata['tenant_id'] === $tenantId) {
                $session->revoke();
                $this->sessionRepository->getEntityManager()->flush();
            }
        }
    }

    public function revokeAllSessions(string $userId): void
    {
        $this->sessionRepository->revokeAllByUserId($userId);
    }

    public function revokeAllSessionsForTenant(string $userId, string $tenantId): void
    {
        $sessions = $this->sessionRepository->findActiveByUserId($userId);
        $revoked = false;
        foreach ($sessions as $session) {
            $metadata = $session->getMetadata();
            if (isset($metadata['tenant_id']) && $metadata['tenant_id'] === $tenantId) {
                $session->revoke();
                $revoked = true;
            }
        }
        
        if ($revoked) {
            $this->sessionRepository->getEntityManager()->flush();
        }
    }

    public function revokeOtherSessions(string $userId, string $currentToken): void
    {
        $this->sessionRepository->revokeAllExcept($userId, $currentToken);
    }

    /**
     * @return array<array<string, mixed>>
     */
    public function getActiveSessions(string $userId): array
    {
        $sessions = $this->sessionRepository->findActiveByUserId($userId);
        return array_map(
            fn(Session $s) => [
                'id' => $s->getId(),
                'token' => substr($s->getToken(), 0, 8) . '...',
                'metadata' => $s->getMetadata(),
                'device_fingerprint' => $s->getDeviceFingerprint(),
                'expires_at' => $s->getExpiresAt()->format('c'),
                'last_activity_at' => $s->getLastActivityAt()->format('c'),
                'created_at' => $s->getCreatedAt()->format('c'),
            ],
            $sessions
        );
    }

    public function refreshSession(string $token): SessionToken
    {
        $session = $this->sessionRepository->findByToken($token);

        if ($session === null || !$session->isValid()) {
            throw new InvalidSessionException('Invalid session');
        }

        $newExpiresAt = new \DateTimeImmutable('+' . self::SESSION_TTL_SECONDS . ' seconds');
        $session->extend($newExpiresAt);
        $this->sessionRepository->getEntityManager()->flush();

        return new SessionToken(
            token: $session->getToken(),
            userId: $session->getUserId(),
            expiresAt: $newExpiresAt,
            metadata: $session->getMetadata() ?? [],
            deviceFingerprint: $session->getDeviceFingerprint(),
            lastActivityAt: $session->getLastActivityAt(),
        );
    }

    public function cleanupExpiredSessions(): int
    {
        return $this->sessionRepository->deleteExpired();
    }

    public function updateActivity(string $sessionId): void
    {
        $session = $this->sessionRepository->find($sessionId);
        if ($session !== null && $session->isValid()) {
            $session->updateActivity();
            $this->sessionRepository->getEntityManager()->flush();
        }
    }

    public function enforceMaxSessions(string $userId, int $max): void
    {
        $sessions = $this->sessionRepository->findActiveByUserId($userId);

        if (count($sessions) <= $max) {
            return;
        }

        // Sort by last activity (oldest first) and revoke excess
        usort($sessions, fn(Session $a, Session $b) => 
            $a->getLastActivityAt() <=> $b->getLastActivityAt()
        );

        $toRevoke = array_slice($sessions, 0, count($sessions) - $max);
        foreach ($toRevoke as $session) {
            $session->revoke();
        }

        $this->sessionRepository->getEntityManager()->flush();
    }

    public function terminateByDeviceId(string $userId, string $fingerprint): void
    {
        $sessions = $this->sessionRepository->findByDeviceFingerprint($userId, $fingerprint);
        foreach ($sessions as $session) {
            $session->revoke();
        }
        $this->sessionRepository->getEntityManager()->flush();
    }

    public function cleanupInactiveSessions(int $inactivityThresholdDays = 7): int
    {
        $threshold = new \DateTimeImmutable("-{$inactivityThresholdDays} days");
        $sessions = $this->sessionRepository->findInactiveSessions($threshold);

        foreach ($sessions as $session) {
            $session->revoke();
        }

        $this->sessionRepository->getEntityManager()->flush();
        return count($sessions);
    }
}
