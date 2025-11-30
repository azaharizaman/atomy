<?php

declare(strict_types=1);

namespace Nexus\Crypto\ValueObjects;

use Nexus\Crypto\Enums\SymmetricAlgorithm;

/**
 * Encrypted Data Value Object
 *
 * Represents encrypted data with all required decryption metadata.
 * Immutable value object containing ciphertext, IV, authentication tag, and algorithm.
 */
final readonly class EncryptedData
{
    /**
     * @param string $ciphertext Base64-encoded encrypted data
     * @param string $iv Base64-encoded initialization vector
     * @param string $tag Base64-encoded authentication tag (for AEAD modes)
     * @param SymmetricAlgorithm $algorithm Algorithm used for encryption
     * @param array<string, mixed> $metadata Additional context (e.g., key ID, timestamp)
     */
    public function __construct(
        public string $ciphertext,
        public string $iv,
        public string $tag,
        public SymmetricAlgorithm $algorithm,
        public array $metadata = [],
    ) {}
    
    /**
     * Convert to array representation for serialization/storage
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'ciphertext' => $this->ciphertext,
            'iv' => $this->iv,
            'tag' => $this->tag,
            'algorithm' => $this->algorithm->value,
            'metadata' => $this->metadata,
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
            ciphertext: $data['ciphertext'],
            iv: $data['iv'],
            tag: $data['tag'] ?? '',
            algorithm: SymmetricAlgorithm::from($data['algorithm']),
            metadata: $data['metadata'] ?? [],
        );
    }
    
    /**
     * Convert to JSON string
     */
    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR);
    }
    
    /**
     * Create from JSON string
     */
    public static function fromJson(string $json): self
    {
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        return self::fromArray($data);
    }
    
    /**
     * Get ciphertext as binary string
     */
    public function getCiphertextBinary(): string
    {
        $decoded = base64_decode($this->ciphertext, true);
        if ($decoded === false) {
            throw new \InvalidArgumentException('Invalid base64-encoded ciphertext');
        }
        return $decoded;
    }
    
    /**
     * Get IV as binary string
     */
    public function getIVBinary(): string
    {
        $decoded = base64_decode($this->iv, true);
        if ($decoded === false) {
            throw new \InvalidArgumentException('Invalid base64-encoded IV');
        }
        return $decoded;
    }
    
    /**
     * Get tag as binary string
     */
    public function getTagBinary(): string
    {
        $decoded = base64_decode($this->tag, true);
        if ($decoded === false) {
            throw new \InvalidArgumentException('Invalid base64-encoded tag');
        }
        return $decoded;
    }
}
