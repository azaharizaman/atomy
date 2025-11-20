<?php

declare(strict_types=1);

namespace Nexus\Crypto\ValueObjects;

use Nexus\Crypto\Enums\HashAlgorithm;

/**
 * Hash Result Value Object
 *
 * Represents the result of a hashing operation.
 * Immutable value object containing hash, algorithm, and optional salt.
 */
final readonly class HashResult
{
    /**
     * @param string $hash Hex-encoded hash output
     * @param HashAlgorithm $algorithm Algorithm used for hashing
     * @param string|null $salt Optional salt (for key derivation functions)
     */
    public function __construct(
        public string $hash,
        public HashAlgorithm $algorithm,
        public ?string $salt = null,
    ) {}
    
    /**
     * Convert to array representation for serialization
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'hash' => $this->hash,
            'algorithm' => $this->algorithm->value,
            'salt' => $this->salt,
        ];
    }
    
    /**
     * Create from array representation
     *
     * @param array<string, mixed> $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            hash: $data['hash'],
            algorithm: HashAlgorithm::from($data['algorithm']),
            salt: $data['salt'] ?? null,
        );
    }
    
    /**
     * Get hash as binary string
     */
    public function getBinary(): string
    {
        $binary = hex2bin($this->hash);
        if ($binary === false) {
            throw new \InvalidArgumentException('Invalid hex-encoded hash');
        }
        return $binary;
    }
    
    /**
     * Check if hash matches expected value (constant-time comparison)
     */
    public function matches(string $expectedHash): bool
    {
        return hash_equals($this->hash, $expectedHash);
    }
}
