<?php

declare(strict_types=1);

namespace Nexus\Domain\Identity\ValueObjects;

use DateTimeImmutable;
use InvalidArgumentException;

/**
 * Represents a single backup code for MFA recovery.
 *
 * Backup codes are one-time use codes that allow users to regain access
 * to their account if they lose their primary MFA device. Each code can
 * only be consumed once and tracks when it was used.
 *
 * @immutable
 */
final readonly class BackupCode
{
    /**
     * Create a new backup code.
     *
     * @param string $code The plaintext code (8+ alphanumeric characters)
     * @param string|null $hash The Argon2id hash of the code (for storage)
     * @param DateTimeImmutable|null $consumedAt When the code was consumed (null if unused)
     * @throws InvalidArgumentException If code is invalid
     */
    public function __construct(
        public string $code,
        public ?string $hash = null,
        public ?DateTimeImmutable $consumedAt = null,
    ) {
        $this->validateCode($code);
        
        if ($hash !== null) {
            $this->validateHash($hash);
        }
    }

    /**
     * Validate the plaintext code format.
     *
     * @throws InvalidArgumentException If code is invalid
     */
    private function validateCode(string $code): void
    {
        if (strlen($code) < 8) {
            throw new InvalidArgumentException('Backup code must be at least 8 characters long');
        }

        // Must be alphanumeric (no special characters for better UX)
        if (!preg_match('/^[A-Z0-9]+$/', $code)) {
            throw new InvalidArgumentException('Backup code must contain only uppercase letters and numbers');
        }
    }

    /**
     * Validate the hash format.
     *
     * @throws InvalidArgumentException If hash is invalid
     */
    private function validateHash(string $hash): void
    {
        // Argon2id hashes start with $argon2id$
        if (!str_starts_with($hash, '$argon2id$')) {
            throw new InvalidArgumentException('Backup code hash must be Argon2id format');
        }
    }

    /**
     * Check if this code has been consumed.
     */
    public function isConsumed(): bool
    {
        return $this->consumedAt !== null;
    }

    /**
     * Mark this code as consumed at the given time.
     *
     * @throws InvalidArgumentException If code is already consumed
     */
    public function consume(DateTimeImmutable $timestamp): self
    {
        if ($this->isConsumed()) {
            throw new InvalidArgumentException('Backup code has already been consumed');
        }

        return new self(
            code: $this->code,
            hash: $this->hash,
            consumedAt: $timestamp,
        );
    }

    /**
     * Create a copy with a hash (for storage).
     *
     * This is used when converting a plaintext code to a stored version.
     */
    public function withHash(string $hash): self
    {
        return new self(
            code: $this->code,
            hash: $hash,
            consumedAt: $this->consumedAt,
        );
    }

    /**
     * Convert to array representation (for storage).
     *
     * @return array{code: string, hash: string|null, consumed_at: string|null}
     */
    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'hash' => $this->hash,
            'consumed_at' => $this->consumedAt?->format('Y-m-d H:i:s'),
        ];
    }
}
