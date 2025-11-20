<?php

declare(strict_types=1);

namespace Nexus\Crypto\ValueObjects;

use DateTimeImmutable;
use Nexus\Crypto\Enums\SymmetricAlgorithm;

/**
 * Encryption Key Value Object
 *
 * Represents a symmetric encryption key with lifecycle metadata.
 * Immutable value object containing key material, algorithm, and expiration.
 *
 * Note: Keys should be stored encrypted (envelope encryption) using master key.
 */
final readonly class EncryptionKey
{
    /**
     * @param string $key Base64-encoded key material
     * @param SymmetricAlgorithm $algorithm Algorithm this key is used for
     * @param DateTimeImmutable $createdAt When the key was created
     * @param DateTimeImmutable|null $expiresAt When the key expires (null = never)
     */
    public function __construct(
        public string $key,
        public SymmetricAlgorithm $algorithm,
        public DateTimeImmutable $createdAt,
        public ?DateTimeImmutable $expiresAt = null,
    ) {}
    
    /**
     * Check if key is expired at the given time
     *
     * @param DateTimeImmutable $now Current time (injected for testing)
     */
    public function isExpired(DateTimeImmutable $now): bool
    {
        if ($this->expiresAt === null) {
            return false; // Never expires
        }
        
        return $now >= $this->expiresAt;
    }
    
    /**
     * Get key as binary string
     */
    public function getKeyBinary(): string
    {
        $decoded = base64_decode($this->key, true);
        if ($decoded === false) {
            throw new \InvalidArgumentException('Invalid base64-encoded key material');
        }
        return $decoded;
    }
    
    /**
     * Convert to array representation (WARNING: Contains key material)
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'algorithm' => $this->algorithm->value,
            'createdAt' => $this->createdAt->format('c'),
            'expiresAt' => $this->expiresAt?->format('c'),
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
            key: $data['key'],
            algorithm: SymmetricAlgorithm::from($data['algorithm']),
            createdAt: new DateTimeImmutable($data['createdAt']),
            expiresAt: isset($data['expiresAt']) ? new DateTimeImmutable($data['expiresAt']) : null,
        );
    }
    
    /**
     * Create a new key with updated expiration date
     */
    public function withExpiresAt(?DateTimeImmutable $expiresAt): self
    {
        return new self(
            key: $this->key,
            algorithm: $this->algorithm,
            createdAt: $this->createdAt,
            expiresAt: $expiresAt,
        );
    }
    
    /**
     * Get days until expiration (null if never expires)
     */
    public function getDaysUntilExpiration(DateTimeImmutable $now): ?int
    {
        if ($this->expiresAt === null) {
            return null;
        }
        
        $diff = $now->diff($this->expiresAt);
        return $diff->invert ? 0 : (int) $diff->days;
    }
}
