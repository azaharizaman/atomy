<?php

declare(strict_types=1);

namespace Nexus\Crypto\ValueObjects;

use Nexus\Crypto\Enums\AsymmetricAlgorithm;

/**
 * Key Pair Value Object
 *
 * Represents an asymmetric cryptography key pair.
 * Immutable value object containing public key, private key, and algorithm.
 */
final readonly class KeyPair
{
    /**
     * @param string $publicKey Base64-encoded public key
     * @param string $privateKey Base64-encoded private key
     * @param AsymmetricAlgorithm $algorithm Algorithm used to generate the key pair
     */
    public function __construct(
        public string $publicKey,
        public string $privateKey,
        public AsymmetricAlgorithm $algorithm,
    ) {}
    
    /**
     * Check if key pair uses quantum-resistant algorithm
     */
    public function isQuantumResistant(): bool
    {
        return $this->algorithm->isQuantumResistant();
    }
    
    /**
     * Convert to array representation (WARNING: Contains private key)
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'publicKey' => $this->publicKey,
            'privateKey' => $this->privateKey,
            'algorithm' => $this->algorithm->value,
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
            publicKey: $data['publicKey'],
            privateKey: $data['privateKey'],
            algorithm: AsymmetricAlgorithm::from($data['algorithm']),
        );
    }
    
    /**
     * Get public key as binary string
     */
    public function getPublicKeyBinary(): string
    {
        $decoded = base64_decode($this->publicKey, true);
        if ($decoded === false) {
            throw new \InvalidArgumentException('Invalid base64-encoded public key');
        }
        return $decoded;
    }
    
    /**
     * Get private key as binary string
     */
    public function getPrivateKeyBinary(): string
    {
        $decoded = base64_decode($this->privateKey, true);
        if ($decoded === false) {
            throw new \InvalidArgumentException('Invalid base64-encoded private key');
        }
        return $decoded;
    }
    
    /**
     * Export public key only (safe for sharing)
     *
     * @return array<string, mixed>
     */
    public function exportPublicKey(): array
    {
        return [
            'publicKey' => $this->publicKey,
            'algorithm' => $this->algorithm->value,
        ];
    }
}
