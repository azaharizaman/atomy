<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\Services;

/**
 * Interface for token management.
 */
interface TokenManagerInterface
{
    public function generateAccessToken(string $userId, ?string $tenantId = null): string;
    public function generateRefreshToken(string $userId): string;
    public function validateRefreshToken(string $refreshToken): array;
    public function createSession(string $userId, string $accessToken): string;
    public function invalidateSession(string $sessionId): void;
    public function invalidateUserSessions(string $userId): void;
}
