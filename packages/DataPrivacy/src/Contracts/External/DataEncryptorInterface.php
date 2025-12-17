<?php

declare(strict_types=1);

namespace Nexus\DataPrivacy\Contracts\External;

/**
 * External interface for encrypting/decrypting personal data.
 *
 * This interface must be implemented by the consuming application,
 * typically using the Nexus\Crypto package.
 */
interface DataEncryptorInterface
{
    /**
     * Encrypt personal data.
     *
     * @param string $data Data to encrypt
     * @param array<string, mixed> $context Additional context for encryption
     * @return string Encrypted data
     */
    public function encrypt(string $data, array $context = []): string;

    /**
     * Decrypt personal data.
     *
     * @param string $encryptedData Encrypted data
     * @param array<string, mixed> $context Additional context for decryption
     * @return string Decrypted data
     */
    public function decrypt(string $encryptedData, array $context = []): string;

    /**
     * Hash data for pseudonymization.
     *
     * @param string $data Data to hash
     * @param string $salt Salt for hashing
     * @return string Hashed data
     */
    public function hash(string $data, string $salt): string;

    /**
     * Generate a pseudonym for a data subject.
     *
     * @param string $dataSubjectId Original data subject ID
     * @return string Pseudonymized identifier
     */
    public function pseudonymize(string $dataSubjectId): string;

    /**
     * Securely delete data by overwriting.
     * Returns true if secure deletion was performed.
     */
    public function secureDelete(string $data): bool;
}
