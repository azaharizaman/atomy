<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\MfaChallenge;

interface MfaChallengeStoreInterface
{
    public function create(string $userId, string $tenantId, string $method): string;

    public function find(string $challengeId, string $tenantId): ?MfaChallenge;

    public function consume(string $challengeId, string $tenantId): void;

    public function incrementAttempts(string $challengeId, string $tenantId): void;
}
