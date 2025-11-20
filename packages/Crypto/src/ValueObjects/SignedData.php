<?php

declare(strict_types=1);

namespace Nexus\Crypto\ValueObjects;

use Nexus\Crypto\Enums\AsymmetricAlgorithm;

/**
 * Signed Data Value Object
 *
 * Represents digitally signed data with signature and verification metadata.
 * Immutable value object containing original data, signature, algorithm, and public key.
 */
final readonly class SignedData
{
    /**
     * @param string $data Original data that was signed
     * @param string $signature Base64-encoded signature bytes
     * @param AsymmetricAlgorithm $algorithm Algorithm used for signing
     * @param string|null $publicKey Base64-encoded public key (optional, for verification)
     */
    public function __construct(
        public string $data,
        public string $signature,
        public AsymmetricAlgorithm $algorithm,
        public ?string $publicKey = null,
    ) {}
    
    /**
     * Check if signature uses quantum-resistant algorithm
     */
    public function isQuantumResistant(): bool
    {
        return $this->algorithm->isQuantumResistant();
    }
    
    /**
     * Convert to array representation for serialization
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'data' => $this->data,
            'signature' => $this->signature,
            'algorithm' => $this->algorithm->value,
            'publicKey' => $this->publicKey,
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
            data: $data['data'],
            signature: $data['signature'],
            algorithm: AsymmetricAlgorithm::from($data['algorithm']),
            publicKey: $data['publicKey'] ?? null,
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
        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        return self::fromArray($decoded);
    }
    
    /**
     * Get signature as binary string
     */
    public function getSignatureBinary(): string
    {
        $decoded = base64_decode($this->signature, true);
        if ($decoded === false) {
            throw new \InvalidArgumentException('Invalid base64-encoded signature');
        }
        return $decoded;
    }
    
    /**
     * Get public key as binary string
     */
    public function getPublicKeyBinary(): ?string
    {
        if ($this->publicKey === null) {
            return null;
        }
        
        $decoded = base64_decode($this->publicKey, true);
        if ($decoded === false) {
            throw new \InvalidArgumentException('Invalid base64-encoded public key');
        }
        return $decoded;
    }
}
