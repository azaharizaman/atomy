<?php

declare(strict_types=1);

namespace Nexus\Crypto\Contracts;

use Nexus\Crypto\Enums\SymmetricAlgorithm;
use Nexus\Crypto\ValueObjects\EncryptedData;
use Nexus\Crypto\ValueObjects\EncryptionKey;

/**
 * Symmetric Encryptor Interface
 *
 * Provides symmetric encryption for data-at-rest and data-in-transit protection.
 * Implemented by the service layer (e.g., SodiumEncryptor, OpenSSLEncryptor).
 */
interface SymmetricEncryptorInterface
{
    /**
     * Encrypt plaintext with specified algorithm
     *
     * If no key is provided, uses the default application encryption key.
     *
     * @param string $plaintext Data to encrypt
     * @param SymmetricAlgorithm $algorithm Encryption algorithm (default: AES256GCM)
     * @param EncryptionKey|null $key Encryption key (null = use default)
     * @return EncryptedData Encrypted data with IV and tag
     */
    public function encrypt(
        string $plaintext,
        SymmetricAlgorithm $algorithm = SymmetricAlgorithm::AES256GCM,
        ?EncryptionKey $key = null
    ): EncryptedData;
    
    /**
     * Decrypt ciphertext
     *
     * @param EncryptedData $encrypted Encrypted data with metadata
     * @param EncryptionKey|null $key Decryption key (null = use default)
     * @return string Decrypted plaintext
     * @throws \Nexus\Crypto\Exceptions\DecryptionException If decryption fails
     */
    public function decrypt(EncryptedData $encrypted, ?EncryptionKey $key = null): string;
}
