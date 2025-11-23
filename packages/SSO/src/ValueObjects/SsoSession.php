<?php

declare(strict_types=1);

namespace Nexus\SSO\ValueObjects;

use DateTimeImmutable;

/**
 * SSO Session Value Object
 * 
 * Represents an authenticated SSO session with user profile and tokens
 * Immutable by design (readonly properties)
 */
final readonly class SsoSession
{
    /**
     * @param string $sessionId Unique session identifier
     * @param string $providerName SSO provider name
     * @param UserProfile $userProfile User profile from SSO provider
     * @param string $accessToken OAuth2/OIDC access token or SAML assertion
     * @param string|null $refreshToken OAuth2/OIDC refresh token (optional)
     * @param DateTimeImmutable $createdAt Session creation timestamp
     * @param DateTimeImmutable $expiresAt Session expiration timestamp
     */
    public function __construct(
        public string $sessionId,
        public string $providerName,
        public UserProfile $userProfile,
        public string $accessToken,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $expiresAt,
        public ?string $refreshToken = null,
    ) {
    }

    /**
     * Check if session has expired
     */
    public function isExpired(): bool
    {
        return $this->expiresAt < new DateTimeImmutable();
    }

    /**
     * Get seconds until session expires
     * Returns 0 if already expired
     */
    public function getSecondsUntilExpiry(): int
    {
        $now = new DateTimeImmutable();
        
        if ($this->expiresAt < $now) {
            return 0;
        }
        
        return $this->expiresAt->getTimestamp() - $now->getTimestamp();
    }
}
