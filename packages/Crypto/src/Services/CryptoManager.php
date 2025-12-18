<?php

declare(strict_types=1);

namespace Nexus\Crypto\Services;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Nexus\Crypto\Contracts\AnonymizerInterface;
use Nexus\Crypto\Contracts\AsymmetricSignerInterface;
use Nexus\Crypto\Contracts\CryptoManagerInterface;
use Nexus\Crypto\Contracts\DataMaskerInterface;
use Nexus\Crypto\Contracts\HasherInterface;
use Nexus\Crypto\Contracts\KeyGeneratorInterface;
use Nexus\Crypto\Contracts\KeyRotationServiceInterface;
use Nexus\Crypto\Contracts\KeyStorageInterface;
use Nexus\Crypto\Contracts\SymmetricEncryptorInterface;
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
 * Crypto Manager
 *
 * Facade providing unified interface to all cryptographic operations.
 * Orchestrates hashing, encryption, signing, key management,
 * anonymization, pseudonymization, and data masking services.
 *
 * Data Protection Capabilities (v1.1):
 * - Irreversible anonymization (hash-based, k-anonymity, suppression)
 * - Reversible pseudonymization (encryption-based with key management)
 * - Data masking (format-preserving, compliance-aware patterns)
 */
final readonly class CryptoManager implements CryptoManagerInterface, KeyRotationServiceInterface
{
    private ?AnonymizerInterface $anonymizer;
    private ?DataMaskerInterface $dataMasker;

    public function __construct(
        private HasherInterface $hasher,
        private SymmetricEncryptorInterface $encryptor,
        private AsymmetricSignerInterface $signer,
        private KeyGeneratorInterface $keyGenerator,
        private KeyStorageInterface $keyStorage,
        private LoggerInterface $logger = new NullLogger(),
        ?AnonymizerInterface $anonymizer = null,
        ?DataMaskerInterface $dataMasker = null,
    ) {
        // Create default implementations if not provided
        $this->anonymizer = $anonymizer ?? new Anonymizer(
            $this->hasher,
            $this->encryptor,
            $this->keyStorage,
            $this->logger,
        );
        $this->dataMasker = $dataMasker ?? new DataMasker($this->logger);
    }
    
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
    
    // =====================================================
    // ANONYMIZATION OPERATIONS
    // =====================================================
    
    /**
     * Anonymize data (irreversible)
     *
     * @param string $data The data to anonymize
     * @param AnonymizationMethod $method The anonymization method to use
     * @param array<string, mixed> $options Method-specific options
     * @return AnonymizedData The anonymized result
     */
    public function anonymize(
        string $data,
        AnonymizationMethod $method = AnonymizationMethod::SALTED_HASH,
        array $options = []
    ): AnonymizedData {
        $this->logger->debug('Anonymizing data via CryptoManager', [
            'method' => $method->value,
        ]);
        
        return $this->anonymizer->anonymize($data, $method, $options);
    }
    
    /**
     * Pseudonymize data (reversible with key)
     *
     * @param string $data The data to pseudonymize
     * @param string $keyId The key ID to use for encryption
     * @return PseudonymizedData The pseudonymized result
     */
    public function pseudonymize(string $data, string $keyId): PseudonymizedData
    {
        $this->logger->debug('Pseudonymizing data via CryptoManager', [
            'keyId' => $keyId,
        ]);
        
        return $this->anonymizer->pseudonymize($data, $keyId);
    }
    
    /**
     * Reverse pseudonymization to recover original data
     *
     * @param PseudonymizedData $pseudonymized The pseudonymized data
     * @return string The original data
     */
    public function dePseudonymize(PseudonymizedData $pseudonymized): string
    {
        $this->logger->debug('De-pseudonymizing data via CryptoManager', [
            'keyId' => $pseudonymized->keyId,
        ]);
        
        return $this->anonymizer->dePseudonymize($pseudonymized);
    }
    
    /**
     * Generate a consistent pseudonym for data within a context
     *
     * Same data + same context + same key = same pseudonym
     *
     * @param string $data The data to pseudonymize
     * @param string $context The context (e.g., 'customer_id', 'email')
     * @param string $keyId The key ID to use for HMAC
     * @return string The deterministic pseudonym
     */
    public function generatePseudonym(string $data, string $context, string $keyId): string
    {
        return $this->anonymizer->generatePseudonym($data, $context, $keyId);
    }
    
    /**
     * Verify if data matches an anonymized value
     *
     * Only works for deterministic anonymization methods.
     *
     * @param string $data The data to verify
     * @param AnonymizedData $anonymized The anonymized value to check against
     * @param array<string, mixed> $options Method-specific options (for HMAC, must include keyId)
     * @return bool True if data matches the anonymized value
     */
    public function verifyAnonymized(
        string $data,
        AnonymizedData $anonymized,
        array $options = []
    ): bool {
        return $this->anonymizer->verifyAnonymized($data, $anonymized, $options);
    }
    
    // =====================================================
    // DATA MASKING OPERATIONS
    // =====================================================
    
    /**
     * Mask data using a predefined pattern
     *
     * Applies a standard masking pattern appropriate for the data type.
     * Patterns are designed to comply with industry standards (PCI-DSS, HIPAA, GDPR).
     *
     * @param string $data The sensitive data to mask
     * @param MaskingPattern $pattern Pattern to apply
     * @return string Masked data preserving format structure
     */
    public function mask(string $data, MaskingPattern $pattern): string
    {
        $this->logger->debug('Masking with pattern via CryptoManager', [
            'pattern' => $pattern->value,
        ]);
        
        return $this->dataMasker->mask($data, $pattern);
    }
    
    /**
     * Mask data using custom pattern
     *
     * Pattern characters:
     * - '#' = preserve character
     * - '*' = mask character
     * - Any other = literal character
     *
     * @param string $data The sensitive data to mask
     * @param string $pattern Custom masking pattern using # (keep) and * (mask)
     * @param string $maskChar Character to use for masking (default: '*')
     * @return string Masked data
     */
    public function maskWithPattern(string $data, string $pattern, string $maskChar = '*'): string
    {
        $this->logger->debug('Masking with custom pattern via CryptoManager', [
            'pattern' => $pattern,
        ]);
        
        return $this->dataMasker->maskWithPattern($data, $pattern, $maskChar);
    }
    
    /**
     * Mask email address (shows first few chars + full domain)
     */
    public function maskEmail(string $email): string
    {
        return $this->dataMasker->maskEmail($email);
    }
    
    /**
     * Mask phone number (shows last 4 digits, preserves format)
     */
    public function maskPhone(string $phone): string
    {
        return $this->dataMasker->maskPhone($phone);
    }
    
    /**
     * Mask credit card number (PCI-DSS compliant: shows first 6 and last 4)
     */
    public function maskCreditCard(string $cardNumber): string
    {
        return $this->dataMasker->maskCreditCard($cardNumber);
    }
    
    /**
     * Mask national ID with country-specific formatting
     *
     * Format varies by country:
     * - MY (Malaysia): ******-**-5678 (shows last 4 of 12-digit IC)
     * - US (USA): ***-**-6789 (shows last 4 of SSN)
     * - GB/UK (Britain): AB******* (shows first 2 of NIN)
     * - SG (Singapore): ****567D (shows last 4 of NRIC)
     * - Other: Shows first 2 and last 2 characters
     */
    public function maskNationalId(string $nationalId, string $country): string
    {
        return $this->dataMasker->maskNationalId($nationalId, $country);
    }
    
    /**
     * Mask IBAN (shows country code + check digits and last 4)
     */
    public function maskIban(string $iban): string
    {
        return $this->dataMasker->maskIban($iban);
    }
    
    /**
     * Mask name (shows first letter of each part)
     */
    public function maskName(string $name): string
    {
        return $this->dataMasker->maskName($name);
    }
    
    /**
     * Mask address (partial masking preserving structure)
     */
    public function maskAddress(string $address): string
    {
        return $this->dataMasker->maskAddress($address);
    }
    
    /**
     * Mask date of birth (shows year only)
     */
    public function maskDateOfBirth(string $dob): string
    {
        return $this->dataMasker->maskDateOfBirth($dob);
    }
    
    /**
     * Fully redact data (replace with marker)
     */
    public function redact(string $data, string $replacement = '[REDACTED]'): string
    {
        return $this->dataMasker->redact($data, $replacement);
    }
    
    /**
     * Check if data appears to already be masked
     */
    public function isAlreadyMasked(string $data, string $maskChar = '*'): bool
    {
        return $this->dataMasker->isAlreadyMasked($data, $maskChar);
    }
}
