<?php

declare(strict_types=1);

namespace Nexus\Audit\ValueObjects;

use Nexus\Crypto\ValueObjects\HashAlgorithm;

/**
 * Audit Hash Value Object
 * 
 * Immutable container for cryptographic hash result.
 * Contains both the hash value and the algorithm used.
 */
final readonly class AuditHash
{
    public function __construct(
        public string $value,
        public HashAlgorithm $algorithm = HashAlgorithm::SHA256
    ) {
        if (empty($value)) {
            throw new \InvalidArgumentException('Hash value cannot be empty');
        }

        // Validate hash format based on algorithm
        $this->validateHashFormat();
    }

    /**
     * Create from SHA-256 hash string
     */
    public static function fromSha256(string $hash): self
    {
        return new self($hash, HashAlgorithm::SHA256);
    }

    /**
     * Get hash value as string
     */
    public function toString(): string
    {
        return $this->value;
    }

    /**
     * Get algorithm name
     */
    public function getAlgorithmName(): string
    {
        return $this->algorithm->value;
    }

    /**
     * Compare with another hash
     */
    public function equals(self $other): bool
    {
        return $this->value === $other->value 
            && $this->algorithm === $other->algorithm;
    }

    /**
     * Validate hash format matches algorithm
     */
    private function validateHashFormat(): void
    {
        $expectedLength = match ($this->algorithm) {
            HashAlgorithm::SHA256 => 64, // 32 bytes * 2 hex chars
            HashAlgorithm::SHA384 => 96,
            HashAlgorithm::SHA512 => 128,
            HashAlgorithm::BLAKE2B => 128,
        };

        if (strlen($this->value) !== $expectedLength) {
            throw new \InvalidArgumentException(
                "Invalid hash length for {$this->algorithm->value}: expected {$expectedLength}, got " . strlen($this->value)
            );
        }

        if (!ctype_xdigit($this->value)) {
            throw new \InvalidArgumentException('Hash must be hexadecimal');
        }
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
