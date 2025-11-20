<?php

declare(strict_types=1);

namespace Nexus\Crypto\Services;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Nexus\Crypto\Contracts\AsymmetricSignerInterface;
use Nexus\Crypto\Contracts\HasherInterface;
use Nexus\Crypto\Contracts\KeyGeneratorInterface;
use Nexus\Crypto\Contracts\KeyStorageInterface;
use Nexus\Crypto\Contracts\SymmetricEncryptorInterface;
use Nexus\Crypto\Enums\AsymmetricAlgorithm;
use Nexus\Crypto\Enums\HashAlgorithm;
use Nexus\Crypto\Enums\SymmetricAlgorithm;
use Nexus\Crypto\ValueObjects\EncryptedData;
use Nexus\Crypto\ValueObjects\EncryptionKey;
use Nexus\Crypto\ValueObjects\HashResult;
use Nexus\Crypto\ValueObjects\KeyPair;
use Nexus\Crypto\ValueObjects\SignedData;

/**
 * Crypto Manager
 *
 * Facade providing unified interface to all cryptographic operations.
 * Orchestrates hashing, encryption, signing, and key management services.
 */
final readonly class CryptoManager
{
    public function __construct(
        private HasherInterface $hasher,
        private SymmetricEncryptorInterface $encryptor,
        private AsymmetricSignerInterface $signer,
        private KeyGeneratorInterface $keyGenerator,
        private KeyStorageInterface $keyStorage,
        private LoggerInterface $logger = new NullLogger(),
    ) {}
    
    // =====================================================
    // HASHING OPERATIONS
    // =====================================================
    
    /**
     * Hash data for integrity verification
     */
    public function hash(string $data, HashAlgorithm $algorithm = HashAlgorithm::SHA256): HashResult
    {
        return $this->hasher->hash($data, $algorithm);
    }
    
    /**
     * Verify hash matches data
     */
    public function verifyHash(string $data, HashResult $expectedHash): bool
    {
        return $this->hasher->verify($data, $expectedHash);
    }
    
    // =====================================================
    // SYMMETRIC ENCRYPTION
    // =====================================================
    
    /**
     * Encrypt data with default key
     */
    public function encrypt(
        string $plaintext,
        SymmetricAlgorithm $algorithm = SymmetricAlgorithm::AES256GCM
    ): EncryptedData {
        $this->logger->debug('Encrypting data', ['algorithm' => $algorithm->value]);
        
        return $this->encryptor->encrypt($plaintext, $algorithm);
    }
    
    /**
     * Decrypt data with default key
     */
    public function decrypt(EncryptedData $encrypted): string
    {
        $this->logger->debug('Decrypting data', ['algorithm' => $encrypted->algorithm->value]);
        
        return $this->encryptor->decrypt($encrypted);
    }
    
    /**
     * Encrypt data with specific key ID
     */
    public function encryptWithKey(string $plaintext, string $keyId): EncryptedData
    {
        $key = $this->keyStorage->retrieve($keyId);
        
        $this->logger->debug('Encrypting with key', [
            'keyId' => $keyId,
            'algorithm' => $key->algorithm->value,
        ]);
        
        $encrypted = $this->encryptor->encrypt($plaintext, $key->algorithm, $key);
        
        // Add key ID to metadata
        return new EncryptedData(
            ciphertext: $encrypted->ciphertext,
            iv: $encrypted->iv,
            tag: $encrypted->tag,
            algorithm: $encrypted->algorithm,
            metadata: array_merge($encrypted->metadata, ['keyId' => $keyId]),
        );
    }
    
    /**
     * Decrypt data with specific key ID
     */
    public function decryptWithKey(EncryptedData $encrypted, string $keyId): string
    {
        // Validate key ID matches metadata if present
        if (isset($encrypted->metadata['keyId']) && $encrypted->metadata['keyId'] !== $keyId) {
            throw new \InvalidArgumentException(
                "Key ID mismatch: expected '{$encrypted->metadata['keyId']}', got '{$keyId}'"
            );
        }
        
        $key = $this->keyStorage->retrieve($keyId);
        
        $this->logger->debug('Decrypting with key', ['keyId' => $keyId]);
        
        return $this->encryptor->decrypt($encrypted, $key);
    }
    
    // =====================================================
    // DIGITAL SIGNATURES
    // =====================================================
    
    /**
     * Sign data with private key
     */
    public function sign(
        string $data,
        string $privateKey,
        AsymmetricAlgorithm $algorithm = AsymmetricAlgorithm::ED25519
    ): SignedData {
        $this->logger->debug('Signing data', ['algorithm' => $algorithm->value]);
        
        return $this->signer->sign($data, $privateKey, $algorithm);
    }
    
    /**
     * Verify signature
     */
    public function verifySignature(SignedData $signed, string $publicKey): bool
    {
        $this->logger->debug('Verifying signature', ['algorithm' => $signed->algorithm->value]);
        
        return $this->signer->verify($signed, $publicKey);
    }
    
    /**
     * Generate HMAC signature
     */
    public function hmac(string $data, string $secret): string
    {
        return $this->signer->hmac($data, $secret);
    }
    
    /**
     * Verify HMAC signature
     */
    public function verifyHmac(string $data, string $signature, string $secret): bool
    {
        return $this->signer->verifyHmac($data, $signature, $secret);
    }
    
    // =====================================================
    // KEY MANAGEMENT
    // =====================================================
    
    /**
     * Generate new encryption key and store it
     */
    public function generateEncryptionKey(
        string $keyId,
        SymmetricAlgorithm $algorithm = SymmetricAlgorithm::AES256GCM,
        ?int $expirationDays = 90
    ): EncryptionKey {
        $key = $this->keyGenerator->generateSymmetricKey($algorithm, $expirationDays);
        
        $this->keyStorage->store($keyId, $key);
        
        $this->logger->info('Generated encryption key', [
            'keyId' => $keyId,
            'algorithm' => $algorithm->value,
            'expirationDays' => $expirationDays,
        ]);
        
        return $key;
    }
    
    /**
     * Generate new key pair for signing
     */
    public function generateKeyPair(
        AsymmetricAlgorithm $algorithm = AsymmetricAlgorithm::ED25519
    ): KeyPair {
        $this->logger->info('Generated key pair', ['algorithm' => $algorithm->value]);
        
        return $this->keyGenerator->generateKeyPair($algorithm);
    }
    
    /**
     * Rotate encryption key
     */
    public function rotateKey(string $keyId): EncryptionKey
    {
        $newKey = $this->keyStorage->rotate($keyId);
        
        $this->logger->warning('Rotated encryption key', ['keyId' => $keyId]);
        
        return $newKey;
    }
    
    /**
     * Retrieve encryption key by ID
     */
    public function getKey(string $keyId): EncryptionKey
    {
        return $this->keyStorage->retrieve($keyId);
    }
    
    /**
     * Find keys expiring soon
     *
     * @return array<string>
     */
    public function findExpiringKeys(int $days = 7): array
    {
        return $this->keyStorage->findExpiringKeys($days);
    }
    
    /**
     * Generate cryptographically secure random bytes
     */
    public function randomBytes(int $length): string
    {
        return $this->keyGenerator->generateRandomBytes($length);
    }
}
