<?php

declare(strict_types=1);

namespace Nexus\Laravel\Identity\Adapters;

use App\Models\Session as SessionModel;
use Nexus\Identity\Contracts\SessionManagerInterface;
use Nexus\Identity\Contracts\UserInterface;
use Nexus\Identity\ValueObjects\SessionToken;
use Nexus\Laravel\Identity\Mappers\LaravelUserMapper;

final class DatabaseSessionManager implements SessionManagerInterface
{
    public function createSession(string $userId, array $metadata = []): SessionToken
    {
        $token = bin2hex(random_bytes(32));
        $expiresAt = new \DateTimeImmutable('+1 hour');

        $session = SessionModel::query()->create([
            'id' => $token,
            'user_id' => $userId,
            'expires_at' => $expiresAt,
            'metadata' => $metadata,
            'last_activity_at' => now(),
        ]);

        return new SessionToken(
            token: $token,
            userId: $userId,
            expiresAt: $expiresAt,
            metadata: $metadata,
            lastActivityAt: $session->last_activity_at
        );
    }

    public function validateSession(string $token): UserInterface
    {
        $session = SessionModel::query()->whereKey($token)->where('expires_at', '>', now())->firstOrFail();
        return LaravelUserMapper::fromModel($session->user);
    }

    public function isValid(string $token): bool
    {
        return SessionModel::query()->whereKey($token)->where('expires_at', '>', now())->exists();
    }

    public function revokeSession(string $token): void
    {
        SessionModel::query()->whereKey($token)->delete();
    }

    public function revokeSessionForTenant(string $token, string $tenantId): void
    {
        SessionModel::query()
            ->whereKey($token)
            ->where('metadata->tenant_id', $tenantId)
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
            ->where('metadata->tenant_id', $tenantId)
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
            ->where('expires_at', '>', now())
            ->get()
            ->toArray();
    }

    public function refreshSession(string $token): SessionToken
    {
        $session = SessionModel::query()->whereKey($token)->firstOrFail();
        $expiresAt = new \DateTimeImmutable('+1 hour');
        $session->update(['expires_at' => $expiresAt]);

        return new SessionToken(
            token: $session->id,
            userId: $session->user_id,
            expiresAt: $expiresAt,
            metadata: $session->metadata,
            lastActivityAt: $session->last_activity_at
        );
    }

    public function cleanupExpiredSessions(): int
    {
        return SessionModel::query()->where('expires_at', '<', now())->delete();
    }

    public function updateActivity(string $sessionId): void
    {
        SessionModel::query()->whereKey($sessionId)->update(['last_activity_at' => now()]);
    }

    public function enforceMaxSessions(string $userId, int $max): void
    {
        $sessions = SessionModel::query()
            ->where('user_id', $userId)
            ->orderBy('last_activity_at', 'asc')
            ->get();

        if ($sessions->count() > $max) {
            $toDelete = $sessions->take($sessions->count() - $max);
            foreach ($toDelete as $session) {
                $session->delete();
            }
        }
    }

    public function terminateByDeviceId(string $userId, string $fingerprint): void
    {
        SessionModel::query()
            ->where('user_id', $userId)
            ->where('device_fingerprint', $fingerprint)
            ->delete();
    }

    public function cleanupInactiveSessions(int $inactivityThresholdDays = 7): int
    {
        return SessionModel::query()
            ->where('last_activity_at', '<', now()->subDays($inactivityThresholdDays))
            ->delete();
    }
}
