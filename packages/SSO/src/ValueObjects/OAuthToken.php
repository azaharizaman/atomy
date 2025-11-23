<?php

declare(strict_types=1);

namespace Nexus\SSO\ValueObjects;

/**
 * OAuth token value object
 * 
 * Immutable representation of OAuth2/OIDC access token
 */
final class OAuthToken
{
    public readonly \DateTimeImmutable $issuedAt;

    /**
     * @param string $accessToken OAuth2 access token
     * @param string $tokenType Token type (usually 'Bearer')
     * @param int $expiresIn Token lifetime in seconds
     * @param string|null $refreshToken Refresh token (optional)
     * @param string|null $idToken OIDC ID token (JWT, optional)
     * @param array<string> $scopes Granted scopes
     * @param \DateTimeImmutable|null $issuedAt Token issue timestamp
     */
    public function __construct(
        public readonly string $accessToken,
        public readonly string $tokenType,
        public readonly int $expiresIn,
        public readonly ?string $refreshToken = null,
        public readonly ?string $idToken = null,
        public readonly array $scopes = [],
        ?\DateTimeImmutable $issuedAt = null,
    ) {
        $this->issuedAt = $issuedAt ?? new \DateTimeImmutable();
    }

    /**
     * Check if token is expired
     */
    public function isExpired(): bool
    {
        $expiresAt = $this->issuedAt->modify("+{$this->expiresIn} seconds");
        return new \DateTimeImmutable() >= $expiresAt;
    }

    /**
     * Get expiry timestamp
     */
    public function getExpiresAt(): \DateTimeImmutable
    {
        return $this->issuedAt->modify("+{$this->expiresIn} seconds");
    }

    /**
     * Get seconds until expiry
     */
    public function getSecondsUntilExpiry(): int
    {
        $now = new \DateTimeImmutable();
        $expiresAt = $this->getExpiresAt();
        return max(0, $expiresAt->getTimestamp() - $now->getTimestamp());
    }

    /**
     * Check if token has specific scope
     */
    public function hasScope(string $scope): bool
    {
        return in_array($scope, $this->scopes, true);
    }
}
