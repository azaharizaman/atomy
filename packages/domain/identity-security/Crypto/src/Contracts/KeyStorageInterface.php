<?php

declare(strict_types=1);

namespace Nexus\Crypto\Contracts;

use Nexus\Crypto\ValueObjects\EncryptionKey;

/**
 * Key Storage Interface
 *
 * Manages persistent storage and retrieval of encryption keys.
 * Implemented by the application layer (e.g., DatabaseKeyStorage with encrypted storage backend).
 *
 * Keys are stored encrypted using envelope encryption:
 * - Data Encryption Keys (DEKs) encrypt application data
 * - Master Key (APP_KEY) encrypts DEKs before storage
 */
interface KeyStorageInterface
{
    /**
     * Store encryption key
     *
     * Key is encrypted with master key before storage (envelope encryption).
     *
     * @param string $keyId Unique identifier for the key (e.g., 'tenant-123-finance')
     * @param EncryptionKey $key Encryption key to store
     * @return void
     */
    public function store(string $keyId, EncryptionKey $key): void;
    
    /**
     * Retrieve encryption key
     *
     * Key is decrypted using master key after retrieval.
     *
     * @param string $keyId Key identifier
     * @return EncryptionKey Decrypted encryption key
     * @throws \Nexus\Crypto\Exceptions\InvalidKeyException If key not found or expired
     */
    public function retrieve(string $keyId): EncryptionKey;
    
    /**
     * Rotate encryption key (generate new key, keep old for decryption)
     *
     * Creates a new key with the same ID but incremented version.
     * Old key is retained for decrypting existing data.
     *
     * @param string $keyId Key identifier to rotate
     * @return EncryptionKey New encryption key
     */
    public function rotate(string $keyId): EncryptionKey;
    
    /**
     * Delete encryption key
     *
     * WARNING: This will make encrypted data undecryptable.
     * Only use after re-encrypting all data with a new key.
     *
     * @param string $keyId Key identifier to delete
     * @return void
     */
    public function delete(string $keyId): void;
    
    /**
     * List all keys expiring within specified days
     *
     * Used by KeyRotationHandler to identify keys needing rotation.
     *
     * @param int $days Number of days threshold
     * @return array<string> Array of key IDs
     */
    public function findExpiringKeys(int $days = 7): array;
}
