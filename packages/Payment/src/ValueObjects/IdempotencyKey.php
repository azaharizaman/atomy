<?php

declare(strict_types=1);

namespace Nexus\Payment\ValueObjects;

use Nexus\Payment\Exceptions\InvalidIdempotencyKeyException;

/**
 * Idempotency key value object for duplicate payment prevention.
 *
 * Ensures that the same payment is not processed multiple times
 * even if the same request is sent repeatedly.
 */
final readonly class IdempotencyKey
{
    private const DEFAULT_TTL_HOURS = 24;

    /**
     * @param string $key The unique idempotency key
     * @param \DateTimeImmutable $createdAt When the key was created
     * @param \DateTimeImmutable $expiresAt When the key expires
     */
    public function __construct(
        public string $key,
        public \DateTimeImmutable $createdAt,
        public \DateTimeImmutable $expiresAt,
    ) {
        if (trim($key) === '') {
            throw new InvalidIdempotencyKeyException('Idempotency key cannot be empty');
        }

        if (strlen($key) > 255) {
            throw new InvalidIdempotencyKeyException('Idempotency key cannot exceed 255 characters');
        }

        if ($expiresAt <= $createdAt) {
            throw new InvalidIdempotencyKeyException('Expiry time must be after creation time');
        }
    }

    /**
     * Create a new idempotency key with default TTL.
     */
    public static function generate(int $ttlHours = self::DEFAULT_TTL_HOURS): self
    {
        $key = bin2hex(random_bytes(16));
        $createdAt = new \DateTimeImmutable();
        $expiresAt = $createdAt->modify(sprintf('+%d hours', $ttlHours));

        return new self($key, $createdAt, $expiresAt);
    }

    /**
     * Create an idempotency key from a provided string.
     */
    public static function fromString(string $key, int $ttlHours = self::DEFAULT_TTL_HOURS): self
    {
        $createdAt = new \DateTimeImmutable();
        $expiresAt = $createdAt->modify(sprintf('+%d hours', $ttlHours));

        return new self($key, $createdAt, $expiresAt);
    }

    /**
     * Create an idempotency key based on request parameters.
     *
     * @param array<string, mixed> $params Request parameters to hash
     */
    public static function fromParams(array $params, int $ttlHours = self::DEFAULT_TTL_HOURS): self
    {
        $key = hash('sha256', json_encode($params, JSON_THROW_ON_ERROR));
        $createdAt = new \DateTimeImmutable();
        $expiresAt = $createdAt->modify(sprintf('+%d hours', $ttlHours));

        return new self($key, $createdAt, $expiresAt);
    }

    /**
     * Check if the key has expired.
     */
    public function isExpired(?\DateTimeImmutable $now = null): bool
    {
        $now ??= new \DateTimeImmutable();

        return $now >= $this->expiresAt;
    }

    /**
     * Check if the key is still valid.
     */
    public function isValid(?\DateTimeImmutable $now = null): bool
    {
        return !$this->isExpired($now);
    }

    /**
     * Get the idempotency key value.
     */
    public function getValue(): string
    {
        return $this->key;
    }

    /**
     * Get remaining TTL in seconds.
     */
    public function getRemainingTtl(?\DateTimeImmutable $now = null): int
    {
        $now ??= new \DateTimeImmutable();

        if ($this->isExpired($now)) {
            return 0;
        }

        return $this->expiresAt->getTimestamp() - $now->getTimestamp();
    }

    /**
     * Check equality with another key.
     */
    public function equals(self $other): bool
    {
        return $this->key === $other->key;
    }

    /**
     * Convert to string.
     */
    public function __toString(): string
    {
        return $this->key;
    }
}
