<?php

declare(strict_types=1);

namespace App\Services\Identity;

use App\Models\MfaChallenge;
use DateInterval;
use DateTimeImmutable;

final readonly class AtomyMfaChallengeStore
{
    private const CHALLENGE_TTL_SECONDS = 600;
    private const MAX_ATTEMPTS = 5;

    public function create(string $userId, string $tenantId, string $method): string
    {
        $challengeId = bin2hex(random_bytes(32));
        $expiresAt = (new DateTimeImmutable())->add(new DateInterval('PT' . self::CHALLENGE_TTL_SECONDS . 'S'));

        MfaChallenge::query()->create([
            'id' => $challengeId,
            'user_id' => $userId,
            'tenant_id' => $tenantId,
            'method' => $method,
            'expires_at' => $expiresAt,
            'attempt_count' => 0,
        ]);

        return $challengeId;
    }

    public function find(string $challengeId): ?MfaChallenge
    {
        return MfaChallenge::query()->whereKey($challengeId)->first();
    }

    public function consume(string $challengeId): void
    {
        MfaChallenge::query()
            ->whereKey($challengeId)
            ->whereNull('consumed_at')
            ->update(['consumed_at' => new DateTimeImmutable()]);
    }

    public function incrementAttempts(string $challengeId): void
    {
        $challenge = MfaChallenge::query()->whereKey($challengeId)->first();
        if ($challenge === null) {
            return;
        }

        $challenge->attempt_count = (int) ($challenge->attempt_count ?? 0) + 1;

        if ($challenge->attempt_count >= self::MAX_ATTEMPTS) {
            $challenge->consumed_at = new DateTimeImmutable();
        }

        $challenge->save();
    }
}

