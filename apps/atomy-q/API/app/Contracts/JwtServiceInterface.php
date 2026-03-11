<?php

declare(strict_types=1);

namespace App\Contracts;

interface JwtServiceInterface
{
    /**
     * @param array<string, mixed> $claims
     */
    public function issueAccessToken(string $userId, string $tenantId, array $claims = []): string;

    public function issueRefreshToken(string $userId, string $tenantId): string;

    public function decode(string $token): object;

    public function getTtlMinutes(): int;
}
