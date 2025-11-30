<?php

declare(strict_types=1);

namespace Nexus\Domain\Identity\ValueObjects;

/**
 * Session token value object
 * 
 * Represents an authenticated session token
 */
final readonly class SessionToken
{
    /**
     * Create new session token
     * 
     * @param string $token Session token string
     * @param string $userId User identifier
     * @param \DateTimeInterface $expiresAt Expiration timestamp
     * @param array<string, mixed> $metadata Session metadata
     * @param string|null $deviceFingerprint Device fingerprint hash
     * @param \DateTimeInterface|null $lastActivityAt Last activity timestamp
     */
    public function __construct(
        public string $token,
        public string $userId,
        public \DateTimeInterface $expiresAt,
        public array $metadata = [],
        public ?string $deviceFingerprint = null,
        public ?\DateTimeInterface $lastActivityAt = null
    ) {
        if (empty($this->token)) {
            throw new \InvalidArgumentException('Token cannot be empty');
        }

        if (empty($this->userId)) {
            throw new \InvalidArgumentException('User ID cannot be empty');
        }
    }

    /**
     * Check if token is expired
     */
    public function isExpired(): bool
    {
        return $this->expiresAt < new \DateTimeImmutable();
    }

    /**
     * Check if token is valid
     */
    public function isValid(): bool
    {
        return !$this->isExpired();
    }

    /**
     * Get time until expiration in seconds
     */
    public function getTimeToExpiration(): int
    {
        $now = new \DateTimeImmutable();
        $diff = $this->expiresAt->getTimestamp() - $now->getTimestamp();
        return max(0, $diff);
    }

    /**
     * Convert to array
     * 
     * @return array{token: string, user_id: string, expires_at: string, metadata: array<string, mixed>, device_fingerprint: string|null, last_activity_at: string|null}
     */
    public function toArray(): array
    {
        return [
            'token' => $this->token,
            'user_id' => $this->userId,
            'expires_at' => $this->expiresAt->format('Y-m-d H:i:s'),
            'metadata' => $this->metadata,
            'device_fingerprint' => $this->deviceFingerprint,
            'last_activity_at' => $this->lastActivityAt?->format('Y-m-d H:i:s'),
        ];
    }
}
