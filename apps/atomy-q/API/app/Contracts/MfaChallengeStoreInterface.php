<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\MfaChallenge;

interface MfaChallengeStoreInterface
{
    public function create(string $userId, string $tenantId, string $method): string;

    /**
     * Privileged lookup for MFA verification requests that do not carry tenant context.
     * Callers must validate challenge state and use the stored tenant for all writes.
     */
    public function findByChallengeId(string $challengeId): ?MfaChallenge;

    public function find(string $challengeId, string $tenantId): ?MfaChallenge;

    public function consume(string $challengeId, string $tenantId): void;

    public function incrementAttempts(string $challengeId, string $tenantId): void;
}
