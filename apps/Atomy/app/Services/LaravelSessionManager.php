<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Session;
use App\Models\User;
use Nexus\Identity\Contracts\SessionManagerInterface;
use Nexus\Identity\Contracts\UserInterface;
use Nexus\Identity\ValueObjects\SessionToken;
use Nexus\Identity\Exceptions\InvalidSessionException;
use Illuminate\Support\Str;

/**
 * Laravel session manager implementation
 */
final readonly class LaravelSessionManager implements SessionManagerInterface
{
    public function createSession(string $userId, array $metadata = []): SessionToken
    {
        $token = Str::random(64);
        $tokenHash = hash('sha256', $token);
        $expiresAt = now()->addMinutes(config('identity.session.lifetime', 120));

        $session = Session::create([
            'user_id' => $userId,
            'token' => $tokenHash,
            'metadata' => $metadata,
            'expires_at' => $expiresAt,
        ]);

        return new SessionToken(
            token: $token,
            userId: $userId,
            expiresAt: $expiresAt,
            metadata: $metadata
        );
    }

    public function validateSession(string $token): UserInterface
    {
        $tokenHash = hash('sha256', $token);

        $session = Session::where('token', $tokenHash)
            ->whereNull('revoked_at')
            ->first();

        if (!$session || !$session->isValid()) {
            throw new InvalidSessionException('Invalid or expired session');
        }

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
        $tokenHash = hash('sha256', $token);

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
        $tokenHash = hash('sha256', $currentToken);

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
                    'expires_at' => $session->expires_at->toIso8601String(),
                    'created_at' => $session->created_at->toIso8601String(),
                ];
            })
            ->all();
    }

    public function refreshSession(string $token): SessionToken
    {
        $tokenHash = hash('sha256', $token);

        $session = Session::where('token', $tokenHash)
            ->whereNull('revoked_at')
            ->firstOrFail();

        if (!$session->isValid()) {
            throw new InvalidSessionException('Cannot refresh expired session');
        }

        $expiresAt = now()->addMinutes(config('identity.session.lifetime', 120));
        $session->update(['expires_at' => $expiresAt]);

        return new SessionToken(
            token: $token,
            userId: $session->user_id,
            expiresAt: $expiresAt,
            metadata: $session->metadata
        );
    }

    public function cleanupExpiredSessions(): int
    {
        return Session::where('expires_at', '<', now())->delete();
    }
}
