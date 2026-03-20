<?php

declare(strict_types=1);

namespace App\Services\Identity;

use Nexus\Identity\Contracts\TokenManagerInterface;
use Nexus\Identity\Exceptions\InvalidTokenException;
use Nexus\Identity\ValueObjects\ApiToken;

/**
 * In-memory API tokens are not used for Atomy-Q HTTP auth (JWT is separate). Satisfies container wiring for Nexus Identity.
 */
final class AtomyIdentityTokenManagerStub implements TokenManagerInterface
{
    public function generateToken(
        string $userId,
        string $name,
        array $scopes = [],
        ?\DateTimeInterface $expiresAt = null,
    ): ApiToken {
        return new ApiToken(
            id: bin2hex(random_bytes(8)),
            token: bin2hex(random_bytes(24)),
            userId: $userId,
            name: $name,
            scopes: $scopes,
            expiresAt: $expiresAt,
        );
    }

    public function validateToken(string $token): \Nexus\Identity\Contracts\UserInterface
    {
        throw new InvalidTokenException('API token validation is not enabled for Atomy-Q');
    }

    public function isValid(string $token): bool
    {
        return false;
    }

    public function revokeToken(string $tokenId): void
    {
    }

    public function revokeAllTokens(string $userId): void
    {
    }

    public function getUserTokens(string $userId): array
    {
        return [];
    }

    public function getTokenScopes(string $token): array
    {
        return [];
    }

    public function cleanupExpiredTokens(): int
    {
        return 0;
    }
}
