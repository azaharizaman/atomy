<?php

declare(strict_types=1);

namespace Nexus\Crypto\Contracts;

use Nexus\Crypto\Enums\AnonymizationMethod;
use Nexus\Crypto\Enums\AsymmetricAlgorithm;
use Nexus\Crypto\Enums\HashAlgorithm;
use Nexus\Crypto\Enums\MaskingPattern;
use Nexus\Crypto\Enums\SymmetricAlgorithm;
use Nexus\Crypto\ValueObjects\AnonymizedData;
use Nexus\Crypto\ValueObjects\EncryptedData;
use Nexus\Crypto\ValueObjects\EncryptionKey;
use Nexus\Crypto\ValueObjects\HashResult;
use Nexus\Crypto\ValueObjects\KeyPair;
use Nexus\Crypto\ValueObjects\PseudonymizedData;
use Nexus\Crypto\ValueObjects\SignedData;

/**
 * Crypto Manager Interface
 *
 * Unified facade providing access to all cryptographic operations.
 * This is the primary interface for consumers requiring comprehensive
 * cryptographic capabilities including:
 *
 * - **Hashing**: Data integrity verification (SHA-256, SHA-512, BLAKE3)
 * - **Symmetric Encryption**: Data-at-rest protection (AES-256-GCM)
 * - **Digital Signatures**: Data authenticity and non-repudiation (Ed25519, ECDSA)
 * - **Key Management**: Generation, storage, rotation, expiration
 * - **Anonymization**: Irreversible data transformation (GDPR compliance)
 * - **Pseudonymization**: Reversible data transformation with key control
 * - **Data Masking**: Format-preserving display masking (PCI-DSS, HIPAA)
 *
 * Implementation Notes:
 * - This interface aggregates capabilities from specialized interfaces
 * - Consumers needing only specific capabilities should inject those interfaces directly
 * - For example: inject `HasherInterface` if only hashing is required
 *
 * @see \Nexus\Crypto\Services\CryptoManager Default implementation
 * @see HasherInterface For hashing-only operations
 * @see SymmetricEncryptorInterface For encryption-only operations
 * @see AsymmetricSignerInterface For signing-only operations
 * @see AnonymizerInterface For anonymization/pseudonymization only
 * @see DataMaskerInterface For masking-only operations
 */
interface CryptoManagerInterface
{
    // =========================================================================
    // HASHING OPERATIONS
    // =========================================================================

    /**
     * Hash data for integrity verification
     *
     * @param string $data Data to hash
     * @param HashAlgorithm $algorithm Hash algorithm (default: SHA256)
     * @return HashResult Hash result with algorithm metadata
     */
    public function hash(
        string $data,
        HashAlgorithm $algorithm = HashAlgorithm::SHA256
    ): HashResult;

    /**
     * Verify hash matches data
     *
     * Uses constant-time comparison to prevent timing attacks.
     *
     * @param string $data Original data
     * @param HashResult $expectedHash Expected hash result
     * @return bool True if hash matches
     */
    public function verifyHash(string $data, HashResult $expectedHash): bool;

    // =========================================================================
    // SYMMETRIC ENCRYPTION
    // =========================================================================

    /**
     * Encrypt data with default key
     *
     * @param string $plaintext Data to encrypt
     * @param SymmetricAlgorithm $algorithm Encryption algorithm (default: AES256GCM)
     * @return EncryptedData Encrypted data with IV and authentication tag
     */
    public function encrypt(
        string $plaintext,
        SymmetricAlgorithm $algorithm = SymmetricAlgorithm::AES256GCM
    ): EncryptedData;

    /**
     * Decrypt data with default key
     *
     * @param EncryptedData $encrypted Encrypted data with metadata
     * @return string Decrypted plaintext
     * @throws \Nexus\Crypto\Exceptions\DecryptionException If decryption fails
     */
    public function decrypt(EncryptedData $encrypted): string;

    /**
     * Encrypt data with specific key ID
     *
     * @param string $plaintext Data to encrypt
     * @param string $keyId Key identifier for encryption
     * @return EncryptedData Encrypted data with key ID in metadata
     * @throws \Nexus\Crypto\Exceptions\InvalidKeyException If key not found
     */
    public function encryptWithKey(string $plaintext, string $keyId): EncryptedData;

    /**
     * Decrypt data with specific key ID
     *
     * @param EncryptedData $encrypted Encrypted data
     * @param string $keyId Key identifier for decryption
     * @return string Decrypted plaintext
     * @throws \Nexus\Crypto\Exceptions\InvalidKeyException If key not found or mismatch
     * @throws \Nexus\Crypto\Exceptions\DecryptionException If decryption fails
     */
    public function decryptWithKey(EncryptedData $encrypted, string $keyId): string;

    // =========================================================================
    // DIGITAL SIGNATURES
    // =========================================================================

    /**
     * Sign data with private key
     *
     * @param string $data Data to sign
     * @param string $privateKey Private key (base64-encoded)
     * @param AsymmetricAlgorithm $algorithm Signing algorithm (default: ED25519)
     * @return SignedData Signed data with signature
     */
    public function sign(
        string $data,
        string $privateKey,
        AsymmetricAlgorithm $algorithm = AsymmetricAlgorithm::ED25519
    ): SignedData;

    /**
     * Verify signature
     *
     * @param SignedData $signed Signed data to verify
     * @param string $publicKey Public key (base64-encoded)
     * @return bool True if signature is valid
     */
    public function verifySignature(SignedData $signed, string $publicKey): bool;

    /**
     * Generate HMAC signature
     *
     * @param string $data Data to sign
     * @param string $secret Shared secret
     * @return string Hex-encoded HMAC signature
     */
    public function hmac(string $data, string $secret): string;

    /**
     * Verify HMAC signature
     *
     * @param string $data Original data
     * @param string $signature Hex-encoded signature
     * @param string $secret Shared secret
     * @return bool True if signature is valid
     */
    public function verifyHmac(string $data, string $signature, string $secret): bool;

    // =========================================================================
    // KEY MANAGEMENT
    // =========================================================================

    /**
     * Generate new encryption key and store it
     *
     * @param string $keyId Unique identifier for the key
     * @param SymmetricAlgorithm $algorithm Key algorithm (default: AES256GCM)
     * @param int|null $expirationDays Days until key expires (default: 90)
     * @return EncryptionKey Generated encryption key
     */
    public function generateEncryptionKey(
        string $keyId,
        SymmetricAlgorithm $algorithm = SymmetricAlgorithm::AES256GCM,
        ?int $expirationDays = 90
    ): EncryptionKey;

    /**
     * Generate new key pair for signing
     *
     * @param AsymmetricAlgorithm $algorithm Key pair algorithm (default: ED25519)
     * @return KeyPair Generated public/private key pair
     */
    public function generateKeyPair(
        AsymmetricAlgorithm $algorithm = AsymmetricAlgorithm::ED25519
    ): KeyPair;

    /**
     * Rotate encryption key
     *
     * Creates a new key version while retaining old key for decryption.
     *
     * @param string $keyId Key identifier to rotate
     * @return EncryptionKey New encryption key
     * @throws \Nexus\Crypto\Exceptions\InvalidKeyException If key not found
     */
    public function rotateKey(string $keyId): EncryptionKey;

    /**
     * Retrieve encryption key by ID
     *
     * @param string $keyId Key identifier
     * @return EncryptionKey Encryption key
     * @throws \Nexus\Crypto\Exceptions\InvalidKeyException If key not found
     */
    public function getKey(string $keyId): EncryptionKey;

    /**
     * Find keys expiring soon
     *
     * @param int $days Number of days threshold (default: 7)
     * @return array<string> Array of key IDs expiring within threshold
     */
    public function findExpiringKeys(int $days = 7): array;

    /**
     * Generate cryptographically secure random bytes
     *
     * Returns raw binary bytes suitable for cryptographic operations.
     * Use bin2hex() to convert to hex string, or base64_encode() for
     * text-safe representation.
     *
     * @param int $length Number of bytes to generate (1 to 1048576)
     * @return string Raw binary bytes (NOT base64-encoded)
     * @throws \InvalidArgumentException If length is invalid
     */
    public function randomBytes(int $length): string;

    // =========================================================================
    // ANONYMIZATION OPERATIONS
    // =========================================================================

    /**
     * Anonymize data (irreversible)
     *
     * Transforms data into a form that cannot be reversed.
     * Suitable for GDPR compliance where data must be anonymized.
     *
     * @param string $data The data to anonymize
     * @param AnonymizationMethod $method Anonymization method (default: SALTED_HASH)
     * @param array<string, mixed> $options Method-specific options
     * @return AnonymizedData Anonymized result with method metadata
     */
    public function anonymize(
        string $data,
        AnonymizationMethod $method = AnonymizationMethod::SALTED_HASH,
        array $options = []
    ): AnonymizedData;

    /**
     * Pseudonymize data (reversible with key)
     *
     * Encrypts data using a specific key, allowing recovery by authorized parties.
     *
     * @param string $data The data to pseudonymize
     * @param string $keyId The key ID to use for encryption
     * @return PseudonymizedData Pseudonymized result with key reference
     */
    public function pseudonymize(string $data, string $keyId): PseudonymizedData;

    /**
     * Reverse pseudonymization to recover original data
     *
     * @param PseudonymizedData $pseudonymized The pseudonymized data
     * @return string The original data
     * @throws \Nexus\Crypto\Exceptions\InvalidKeyException If key not found
     */
    public function dePseudonymize(PseudonymizedData $pseudonymized): string;

    /**
     * Generate a consistent pseudonym for data within a context
     *
     * Same data + same context + same key = same pseudonym.
     * Useful for cross-system correlation without exposing original data.
     *
     * @param string $data The data to pseudonymize
     * @param string $context The context (e.g., 'customer_id', 'email')
     * @param string $keyId The key ID to use for HMAC
     * @return string The deterministic pseudonym
     */
    public function generatePseudonym(string $data, string $context, string $keyId): string;

    /**
     * Verify if data matches an anonymized value
     *
     * Only works for deterministic anonymization methods.
     *
     * @param string $data The data to verify
     * @param AnonymizedData $anonymized The anonymized value to check against
     * @param array<string, mixed> $options Method-specific options
     * @return bool True if data matches the anonymized value
     */
    public function verifyAnonymized(
        string $data,
        AnonymizedData $anonymized,
        array $options = []
    ): bool;

    // =========================================================================
    // DATA MASKING OPERATIONS
    // =========================================================================

    /**
     * Mask data using a predefined pattern
     *
     * @param string $data The sensitive data to mask
     * @param MaskingPattern $pattern Pattern to apply
     * @return string Masked data preserving format structure
     */
    public function mask(string $data, MaskingPattern $pattern): string;

    /**
     * Mask data using custom pattern
     *
     * Pattern characters:
     * - '#' = preserve character
     * - '*' = mask character
     * - Any other = literal character
     *
     * @param string $data The sensitive data to mask
     * @param string $pattern Custom masking pattern
     * @param string $maskChar Character to use for masking (default: '*')
     * @return string Masked data
     */
    public function maskWithPattern(string $data, string $pattern, string $maskChar = '*'): string;

    /**
     * Mask email address
     *
     * Format: j***@example.com
     *
     * @param string $email Email address to mask
     * @return string Masked email
     */
    public function maskEmail(string $email): string;

    /**
     * Mask phone number
     *
     * Format: +1 (***) ***-4567
     *
     * @param string $phone Phone number (any format)
     * @return string Masked phone number
     */
    public function maskPhone(string $phone): string;

    /**
     * Mask credit card number (PCI-DSS compliant)
     *
     * Format: ****-****-****-1234
     *
     * @param string $cardNumber Credit card number
     * @return string Masked card number (last 4 visible)
     */
    public function maskCreditCard(string $cardNumber): string;

    /**
     * Mask national ID with country-specific formatting
     *
     * Format varies by country:
     * - MY (Malaysia): ******-**-5678 (shows last 4 of 12-digit IC)
     * - US (USA): ***-**-6789 (shows last 4 of SSN)
     * - GB/UK (Britain): AB******* (shows first 2 of NIN)
     * - SG (Singapore): ****567D (shows last 4 of NRIC)
     * - Other: Shows first 2 and last 2 characters
     *
     * @param string $nationalId National ID number
     * @param string $country ISO 3166-1 alpha-2 country code (required)
     * @return string Masked national ID
     */
    public function maskNationalId(string $nationalId, string $country): string;

    /**
     * Mask IBAN
     *
     * Format: GB82 **** **** **** **** 12
     *
     * @param string $iban International Bank Account Number
     * @return string Masked IBAN
     */
    public function maskIban(string $iban): string;

    /**
     * Mask name
     *
     * Format: J*** D**
     *
     * @param string $name Full name
     * @return string Masked name
     */
    public function maskName(string $name): string;

    /**
     * Mask address
     *
     * Partial masking preserving structure.
     *
     * @param string $address Full address
     * @return string Masked address
     */
    public function maskAddress(string $address): string;

    /**
     * Mask date of birth
     *
     * Format: ****-**-** (shows year only or similar)
     *
     * @param string $dob Date of birth
     * @return string Masked date
     */
    public function maskDateOfBirth(string $dob): string;

    /**
     * Fully redact data
     *
     * @param string $data Data to redact
     * @param string $replacement Replacement marker (default: '[REDACTED]')
     * @return string Redacted output
     */
    public function redact(string $data, string $replacement = '[REDACTED]'): string;

    /**
     * Check if data appears to already be masked
     *
     * @param string $data Data to check
     * @param string $maskChar Masking character to detect (default: '*')
     * @return bool True if data appears masked
     */
    public function isAlreadyMasked(string $data, string $maskChar = '*'): bool;
}
