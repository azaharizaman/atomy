<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Session;
use App\Models\User;
use Nexus\Identity\Contracts\SessionManagerInterface;
use Nexus\Identity\Contracts\UserInterface;
use Nexus\Identity\ValueObjects\SessionToken;
use Nexus\Identity\Exceptions\InvalidSessionException;
use Nexus\Crypto\Contracts\KeyGeneratorInterface;
use Nexus\Crypto\Contracts\HasherInterface;

/**
 * Laravel session manager implementation
 */
final readonly class LaravelSessionManager implements SessionManagerInterface
{
    public function __construct(
        private KeyGeneratorInterface $keyGenerator,
        private HasherInterface $hasher
    ) {
    }

    public function createSession(string $userId, array $metadata = []): SessionToken
    {
        // Use Crypto package for secure token generation
        $tokenBytes = $this->keyGenerator->generateRandomBytes(32);
        $token = bin2hex($tokenBytes); // Convert binary to hex for safe storage/transmission (raw binary may contain null bytes that cause issues in string fields)
        
        // Hash token for storage (deterministic SHA-256)
        $hashResult = $this->hasher->hash($token);
        $tokenHash = $hashResult->hash;
        
        $expiresAt = now()->addMinutes(config('identity.session.lifetime', 120));

        // Extract device fingerprint and location from metadata
        $deviceFingerprint = $metadata['device_fingerprint'] ?? null;
        $location = $metadata['geographic_location'] ?? null;
        unset($metadata['device_fingerprint'], $metadata['geographic_location']);

        $session = Session::create([
            'user_id' => $userId,
            'token' => $tokenHash,
            'metadata' => $metadata,
            'device_fingerprint' => $deviceFingerprint,
            'geographic_location' => $location,
            'last_activity_at' => now(),
            'expires_at' => $expiresAt,
        ]);

        return new SessionToken(
            token: $token,
            userId: $userId,
            expiresAt: $expiresAt,
            metadata: $metadata,
            deviceFingerprint: $deviceFingerprint,
            lastActivityAt: now()
        );
    }

    public function validateSession(string $token): UserInterface
    {
        $hashResult = $this->hasher->hash($token);
        $tokenHash = $hashResult->hash;

        $session = Session::where('token', $tokenHash)
            ->whereNull('revoked_at')
            ->first();

        if (!$session || !$session->isValid()) {
            throw new InvalidSessionException('Invalid or expired session');
        }

        // last_activity_at is updated by TrackSessionActivity middleware

        return User::findOrFail($session->user_id);
    }

    public function isValid(string $token): bool
    {
        try {
            $this->validateSession($token);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function revokeSession(string $token): void
    {
        $hashResult = $this->hasher->hash($token);
        $tokenHash = $hashResult->hash;

        Session::where('token', $tokenHash)
            ->update(['revoked_at' => now()]);
    }

    public function revokeAllSessions(string $userId): void
    {
        Session::where('user_id', $userId)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);
    }

    public function revokeOtherSessions(string $userId, string $currentToken): void
    {
        $hashResult = $this->hasher->hash($currentToken);
        $tokenHash = $hashResult->hash;

        Session::where('user_id', $userId)
            ->where('token', '!=', $tokenHash)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);
    }

    public function getActiveSessions(string $userId): array
    {
        return Session::where('user_id', $userId)
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($session) {
                return [
                    'id' => $session->id,
                    'metadata' => $session->metadata,
                    'device_fingerprint' => $session->device_fingerprint,
                    'last_activity_at' => $session->last_activity_at?->toIso8601String(),
                    'expires_at' => $session->expires_at->toIso8601String(),
                    'created_at' => $session->created_at->toIso8601String(),
                ];
            })
            ->all();
    }

    public function refreshSession(string $token): SessionToken
    {
        $hashResult = $this->hasher->hash($token);
        $tokenHash = $hashResult->hash;

        $session = Session::where('token', $tokenHash)
            ->whereNull('revoked_at')
            ->firstOrFail();

        if (!$session->isValid()) {
            throw new InvalidSessionException('Cannot refresh expired session');
        }

        $expiresAt = now()->addMinutes(config('identity.session.lifetime', 120));
        $session->update([
            'expires_at' => $expiresAt,
            'last_activity_at' => now(),
        ]);

        return new SessionToken(
            token: $token,
            userId: $session->user_id,
            expiresAt: $expiresAt,
            metadata: $session->metadata,
            deviceFingerprint: $session->device_fingerprint,
            lastActivityAt: now()
        );
    }

    public function cleanupExpiredSessions(): int
    {
        return Session::where('expires_at', '<', now())->delete();
    }

    // ============================================
    // New Enhanced Methods
    // ============================================

    public function updateActivity(string $sessionId): void
    {
        Session::where('id', $sessionId)
            ->whereNull('revoked_at')
            ->update(['last_activity_at' => now()]);
    }

    public function enforceMaxSessions(string $userId, int $max): void
    {
        $sessions = Session::where('user_id', $userId)
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->orderBy('last_activity_at', 'desc')
            ->get();

        if ($sessions->count() <= $max) {
            return;
        }

        // Revoke oldest sessions beyond the limit
        // slice($max) returns all sessions from index $max to the end (0-indexed),
        // effectively keeping the first $max sessions and returning the rest to be revoked.
        $sessionsToRevoke = $sessions->slice($max);
        
        foreach ($sessionsToRevoke as $session) {
            $session->update(['revoked_at' => now()]);
        }
    }

    public function terminateByDeviceId(string $userId, string $fingerprint): void
    {
        Session::where('user_id', $userId)
            ->where('device_fingerprint', $fingerprint)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);
    }

    public function cleanupInactiveSessions(int $inactivityThresholdDays = 7): int
    {
        $threshold = now()->subDays($inactivityThresholdDays);

        return Session::where(function ($query) use ($threshold) {
            $query->where('last_activity_at', '<', $threshold)
                ->orWhere(function ($q) use ($threshold) {
                    $q->whereNull('last_activity_at')
                        ->where('created_at', '<', $threshold);
                });
        })
        ->whereNull('revoked_at')
        ->delete();
    }
}
