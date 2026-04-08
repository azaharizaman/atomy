<?php

declare(strict_types=1);

namespace Nexus\Laravel\Identity\Adapters;

use App\Models\Session as SessionModel;
use App\Models\User as UserModel;
use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use Nexus\Identity\Contracts\SessionManagerInterface;
use Nexus\Identity\Contracts\UserInterface;
use Nexus\Identity\Exceptions\InvalidSessionException;
use Nexus\Identity\ValueObjects\SessionToken;
use Nexus\Laravel\Identity\Mappers\LaravelUserMapper;

final class DatabaseSessionManager implements SessionManagerInterface
{
    public function createSession(string $userId, array $metadata = []): SessionToken
    {
        $now = new DateTimeImmutable();
        $expiresAt = $now->add(new DateInterval('PT1H'));
        $token = bin2hex(random_bytes(32));

        $payload = array_merge($metadata, [
            'expires_at' => $expiresAt->format(DateTimeInterface::ATOM),
        ]);

        SessionModel::query()->create([
            'id' => $token,
            'user_id' => $userId,
            'tenant_id' => $this->normalizeTenantId($metadata['tenant_id'] ?? null),
            'payload' => $payload,
            'last_activity' => $now,
        ]);

        return new SessionToken(
            token: $token,
            userId: $userId,
            expiresAt: $expiresAt,
            metadata: $metadata,
            deviceFingerprint: $this->normalizeString($metadata['device_fingerprint'] ?? null),
            lastActivityAt: $now,
        );
    }

    public function validateSession(string $token): UserInterface
    {
        $session = $this->findActiveSession($token);
        if ($session === null) {
            throw new InvalidSessionException('Session not found or expired');
        }

        $user = UserModel::query()->whereKey($session->user_id)->first();
        if ($user === null) {
            throw new InvalidSessionException('Session user not found');
        }

        return LaravelUserMapper::fromModel($user);
    }

    public function isValid(string $token): bool
    {
        return $this->findActiveSession($token) !== null;
    }

    public function revokeSession(string $token): void
    {
        SessionModel::query()->whereKey($token)->delete();
    }

    public function revokeSessionForTenant(string $token, string $tenantId): void
    {
        SessionModel::query()
            ->whereKey($token)
            ->where('tenant_id', $tenantId)
            ->delete();
    }

    public function revokeAllSessions(string $userId): void
    {
        SessionModel::query()->where('user_id', $userId)->delete();
    }

    public function revokeAllSessionsForTenant(string $userId, string $tenantId): void
    {
        SessionModel::query()
            ->where('user_id', $userId)
            ->where('tenant_id', $tenantId)
            ->delete();
    }

    public function revokeOtherSessions(string $userId, string $currentToken): void
    {
        SessionModel::query()
            ->where('user_id', $userId)
            ->where('id', '!=', $currentToken)
            ->delete();
    }

    public function getActiveSessions(string $userId): array
    {
        return SessionModel::query()
            ->where('user_id', $userId)
            ->orderBy('last_activity', 'desc')
            ->get()
            ->filter(fn (SessionModel $session): bool => $this->sessionIsActive($session))
            ->map(static fn (SessionModel $session): array => $session->toArray())
            ->all();
    }

    public function refreshSession(string $token): SessionToken
    {
        $session = SessionModel::query()->whereKey($token)->firstOrFail();
        $now = new DateTimeImmutable();
        $expiresAt = $now->add(new DateInterval('PT1H'));
        $payload = $this->sessionPayload($session);
        $payload['expires_at'] = $expiresAt->format(DateTimeInterface::ATOM);

        $session->update([
            'payload' => $payload,
            'last_activity' => $now,
        ]);

        return $this->toToken($session, $payload, $now, $expiresAt);
    }

    public function cleanupExpiredSessions(): int
    {
        $deleted = 0;

        SessionModel::query()->chunk(500, function ($sessions) use (&$deleted): void {
            foreach ($sessions as $session) {
                if (! $this->sessionIsActive($session)) {
                    $session->delete();
                    $deleted++;
                }
            }
        });

        return $deleted;
    }

    public function updateActivity(string $sessionId): void
    {
        SessionModel::query()->whereKey($sessionId)->update(['last_activity' => new DateTimeImmutable()]);
    }

    public function enforceMaxSessions(string $userId, int $max): void
    {
        if ($max < 1) {
            return;
        }

        $sessions = SessionModel::query()
            ->where('user_id', $userId)
            ->orderBy('last_activity', 'asc')
            ->get();

        $excess = $sessions->count() - $max;
        if ($excess <= 0) {
            return;
        }

        foreach ($sessions->take($excess) as $session) {
            $session->delete();
        }
    }

    public function terminateByDeviceId(string $userId, string $fingerprint): void
    {
        SessionModel::query()
            ->where('user_id', $userId)
            ->get()
            ->filter(function (SessionModel $session) use ($fingerprint): bool {
                $payload = $this->sessionPayload($session);
                $storedFingerprint = $this->normalizeString($payload['device_fingerprint'] ?? null);

                return $storedFingerprint !== null && $storedFingerprint === $fingerprint;
            })
            ->each(static fn (SessionModel $session): bool => (bool) $session->delete());
    }

    public function cleanupInactiveSessions(int $inactivityThresholdDays = 7): int
    {
        if ($inactivityThresholdDays < 1) {
            $inactivityThresholdDays = 1;
        }

        $threshold = new DateTimeImmutable(sprintf('-%d days', $inactivityThresholdDays));
        $deleted = 0;

        SessionModel::query()->chunk(500, function ($sessions) use (&$deleted, $threshold): void {
            foreach ($sessions as $session) {
                $lastActivity = $session->last_activity instanceof DateTimeInterface
                    ? DateTimeImmutable::createFromInterface($session->last_activity)
                    : new DateTimeImmutable((string) $session->last_activity);

                if ($lastActivity < $threshold) {
                    $session->delete();
                    $deleted++;
                }
            }
        });

        return $deleted;
    }

    private function findActiveSession(string $token): ?SessionModel
    {
        $session = SessionModel::query()->whereKey($token)->first();
        if ($session === null || ! $this->sessionIsActive($session)) {
            return null;
        }

        return $session;
    }

    private function sessionIsActive(SessionModel $session): bool
    {
        $expiresAt = $this->sessionExpiresAt($session);
        if ($expiresAt === null) {
            return false;
        }

        return $expiresAt > new DateTimeImmutable();
    }

    private function sessionExpiresAt(SessionModel $session): ?DateTimeImmutable
    {
        $payload = $this->sessionPayload($session);
        $expiresAt = $this->normalizeString($payload['expires_at'] ?? null);

        if ($expiresAt !== null) {
            try {
                return new DateTimeImmutable($expiresAt);
            } catch (\Throwable) {
                return null;
            }
        }

        if ($session->last_activity instanceof DateTimeInterface) {
            return DateTimeImmutable::createFromInterface($session->last_activity)->add(new DateInterval('PT1H'));
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function sessionPayload(SessionModel $session): array
    {
        return is_array($session->payload) ? $session->payload : [];
    }

    private function toToken(SessionModel $session, array $payload, DateTimeImmutable $lastActivityAt, DateTimeImmutable $expiresAt): SessionToken
    {
        return new SessionToken(
            token: (string) $session->id,
            userId: (string) $session->user_id,
            expiresAt: $expiresAt,
            metadata: $payload,
            deviceFingerprint: $this->normalizeString($payload['device_fingerprint'] ?? null),
            lastActivityAt: $lastActivityAt,
        );
    }

    private function normalizeTenantId(mixed $tenantId): ?string
    {
        return $this->normalizeString($tenantId);
    }

    private function normalizeString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $normalized = trim($value);

        return $normalized === '' ? null : $normalized;
    }
}
