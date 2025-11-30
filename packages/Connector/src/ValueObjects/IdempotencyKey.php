<?php

declare(strict_types=1);

namespace Nexus\Connector\ValueObjects;

/**
 * Immutable idempotency key for preventing duplicate requests.
 *
 * Idempotency keys are used to ensure that retried requests
 * don't result in duplicate operations (e.g., duplicate payments).
 */
final readonly class IdempotencyKey
{
    /**
     * @param string $key Unique idempotency key
     * @param \DateTimeImmutable $expiresAt Expiration time for the key
     */
    public function __construct(
        public string $key,
        public \DateTimeImmutable $expiresAt,
    ) {}

    /**
     * Generate a new idempotency key.
     *
     * @param string $prefix Optional prefix for the key
     * @param int $ttlSeconds Time-to-live in seconds (default: 24 hours)
     */
    public static function generate(string $prefix = '', int $ttlSeconds = 86400): self
    {
        $key = $prefix !== '' 
            ? sprintf('%s_%s', $prefix, bin2hex(random_bytes(16)))
            : bin2hex(random_bytes(16));

        return new self(
            key: $key,
            expiresAt: (new \DateTimeImmutable())->modify("+{$ttlSeconds} seconds")
        );
    }

    /**
     * Create from existing key string.
     *
     * @param string $key Existing idempotency key
     * @param int $ttlSeconds Time-to-live in seconds (default: 24 hours)
     */
    public static function fromString(string $key, int $ttlSeconds = 86400): self
    {
        return new self(
            key: $key,
            expiresAt: (new \DateTimeImmutable())->modify("+{$ttlSeconds} seconds")
        );
    }

    /**
     * Check if the key has expired.
     */
    public function isExpired(): bool
    {
        return $this->expiresAt <= new \DateTimeImmutable();
    }

    /**
     * Get the key as a string.
     */
    public function toString(): string
    {
        return $this->key;
    }

    /**
     * String representation.
     */
    public function __toString(): string
    {
        return $this->key;
    }
}
