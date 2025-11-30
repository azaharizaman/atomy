<?php

declare(strict_types=1);

namespace Nexus\SSO\ValueObjects;

/**
 * Callback state value object
 * 
 * Represents CSRF protection state token with metadata
 * Immutable by design (readonly properties)
 */
final readonly class CallbackState
{
    /**
     * @param string $token Random state token
     * @param array<string, mixed> $metadata State metadata (provider, tenant_id, etc.)
     * @param \DateTimeImmutable $createdAt State creation time
     * @param \DateTimeImmutable $expiresAt State expiration time
     */
    public function __construct(
        public string $token,
        public array $metadata,
        public \DateTimeImmutable $createdAt,
        public \DateTimeImmutable $expiresAt,
    ) {
    }

    public function isExpired(): bool
    {
        return $this->expiresAt < new \DateTimeImmutable();
    }
}
