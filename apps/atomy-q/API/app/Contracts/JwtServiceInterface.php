<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Dtos\JwtPayloadDto;

/**
 * Boundary for issuing and decoding Atomy-Q tenant-scoped JWTs.
 *
 * Implementations must preserve the token type and tenant claims because
 * middleware depends on them to establish authenticated request context.
 */
interface JwtServiceInterface
{
    /**
     * @param array<string, mixed> $claims
     */
    public function issueAccessToken(string $userId, string $tenantId, array $claims = []): string;

    public function issueRefreshToken(string $userId, string $tenantId): string;

    public function decode(string $token): JwtPayloadDto;

    public function getTtlMinutes(): int;
}
