<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\Services;

use Nexus\IdentityOperations\DTOs\RefreshTokenPayload;

/**
 * Interface for token management.
 */
interface TokenManagerInterface extends SessionManagerInterface
{
    public function generateAccessToken(string $userId, string $tenantId): string;
    public function generateRefreshToken(string $userId, string $tenantId): string;
    public function validateRefreshToken(string $refreshToken, string $tenantId): RefreshTokenPayload;
    public function createSession(string $userId, string $accessToken, string $tenantId): string;
}
