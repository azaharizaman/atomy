<?php

declare(strict_types=1);

namespace Nexus\SSO\ValueObjects;

/**
 * SAML assertion value object
 * 
 * Immutable representation of a validated SAML assertion
 */
final readonly class SamlAssertion
{
    /**
     * @param string $nameId SAML NameID (unique user identifier)
     * @param string $sessionIndex SAML session index for Single Logout
     * @param array<string, mixed> $attributes Extracted SAML attributes
     * @param \DateTimeImmutable $notBefore Assertion validity start time
     * @param \DateTimeImmutable $notOnOrAfter Assertion validity end time
     * @param string $issuer IdP entity ID
     * @param string $audience SP entity ID (recipient)
     */
    public function __construct(
        public string $nameId,
        public string $sessionIndex,
        public array $attributes,
        public \DateTimeImmutable $notBefore,
        public \DateTimeImmutable $notOnOrAfter,
        public string $issuer,
        public string $audience,
    ) {}

    /**
     * Check if assertion is currently valid
     */
    public function isValid(): bool
    {
        $now = new \DateTimeImmutable();
        return $now >= $this->notBefore && $now < $this->notOnOrAfter;
    }

    /**
     * Get seconds until expiry
     */
    public function getSecondsUntilExpiry(): int
    {
        $now = new \DateTimeImmutable();
        return max(0, $this->notOnOrAfter->getTimestamp() - $now->getTimestamp());
    }
}
