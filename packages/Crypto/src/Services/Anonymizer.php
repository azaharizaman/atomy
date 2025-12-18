<?php

declare(strict_types=1);

namespace Nexus\Crypto\Services;

use DateTimeImmutable;
use Nexus\Crypto\Contracts\AnonymizerInterface;
use Nexus\Crypto\Contracts\HasherInterface;
use Nexus\Crypto\Contracts\KeyStorageInterface;
use Nexus\Crypto\Contracts\SymmetricEncryptorInterface;
use Nexus\Crypto\Enums\AnonymizationMethod;
use Nexus\Crypto\Enums\HashAlgorithm;
use Nexus\Crypto\Exceptions\AnonymizationException;
use Nexus\Crypto\ValueObjects\AnonymizedData;
use Nexus\Crypto\ValueObjects\EncryptedData;
use Nexus\Crypto\ValueObjects\PseudonymizedData;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Anonymizer Service
 *
 * Production-ready implementation of data anonymization and pseudonymization.
 *
 * This service provides:
 * - Irreversible anonymization (multiple methods)
 * - Reversible pseudonymization (encryption-based)
 * - Consistent pseudonym generation (HMAC-based)
 * - Verification of deterministic anonymization
 *
 * Security Considerations:
 * - Uses cryptographic primitives from existing Crypto package
 * - Logging excludes sensitive data (only method and status)
 * - Key material never logged or exposed
 */
final readonly class Anonymizer implements AnonymizerInterface
{
    private const int KEY_VERSION_DEFAULT = 1;

    public function __construct(
        private HasherInterface $hasher,
        private SymmetricEncryptorInterface $encryptor,
        private KeyStorageInterface $keyStorage,
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    /**
     * @inheritDoc
     */
    public function anonymize(
        string $data,
        AnonymizationMethod $method = AnonymizationMethod::SALTED_HASH,
        array $options = []
    ): AnonymizedData {
        $this->logger->debug('Anonymizing data', [
            'method' => $method->value,
            'has_options' => !empty($options),
        ]);

        // Validate required options for specific methods
        $this->validateOptions($method, $options);

        $salt = null;
        $anonymizedValue = match ($method) {
            AnonymizationMethod::HASH_BASED => $this->hashBasedAnonymize($data),
            AnonymizationMethod::SALTED_HASH => $this->saltedHashAnonymize($data, $salt),
            AnonymizationMethod::HMAC_BASED => $this->hmacBasedAnonymize($data, $options),
            AnonymizationMethod::K_ANONYMITY => $this->kAnonymityAnonymize($data, $options),
            AnonymizationMethod::SUPPRESSION => $this->suppressionAnonymize(),
        };

        $this->logger->info('Data anonymized successfully', [
            'method' => $method->value,
            'security_level' => $method->getSecurityLevel(),
        ]);

        return new AnonymizedData(
            anonymizedValue: $anonymizedValue,
            method: $method,
            anonymizedAt: new DateTimeImmutable(),
            salt: $salt,
            metadata: [
                'security_level' => $method->getSecurityLevel(),
                'is_deterministic' => $method->isDeterministic(),
            ],
        );
    }

    /**
     * @inheritDoc
     */
    public function pseudonymize(string $data, string $keyId): PseudonymizedData
    {
        $this->logger->debug('Pseudonymizing data', ['keyId' => $keyId]);

        try {
            $key = $this->keyStorage->retrieve($keyId);
            
            // Encrypt the data using the retrieved key
            $encrypted = $this->encryptor->encrypt($data, $key->algorithm, $key);
            
            // Serialize encrypted data for storage
            $pseudonym = $this->serializeEncryptedData($encrypted);
            
            $this->logger->info('Data pseudonymized successfully', ['keyId' => $keyId]);
            
            return new PseudonymizedData(
                pseudonym: $pseudonym,
                keyId: $keyId,
                keyVersion: self::KEY_VERSION_DEFAULT,
                pseudonymizedAt: new DateTimeImmutable(),
                metadata: [
                    'algorithm' => $key->algorithm->value,
                ],
            );
        } catch (\Throwable $e) {
            $this->logger->error('Pseudonymization failed', [
                'keyId' => $keyId,
                'error' => $e->getMessage(),
            ]);
            
            throw AnonymizationException::pseudonymizationFailed($e->getMessage());
        }
    }

    /**
     * @inheritDoc
     */
    public function dePseudonymize(PseudonymizedData $pseudonymized): string
    {
        $this->logger->debug('De-pseudonymizing data', [
            'keyId' => $pseudonymized->keyId,
            'keyVersion' => $pseudonymized->keyVersion,
        ]);

        try {
            $key = $this->keyStorage->retrieve($pseudonymized->keyId);
            
            // Deserialize the encrypted data
            $encrypted = $this->deserializeEncryptedData($pseudonymized->pseudonym);
            
            // Decrypt using the key
            $plaintext = $this->encryptor->decrypt($encrypted, $key);
            
            $this->logger->info('Data de-pseudonymized successfully', [
                'keyId' => $pseudonymized->keyId,
            ]);
            
            return $plaintext;
        } catch (\Throwable $e) {
            $this->logger->error('De-pseudonymization failed', [
                'keyId' => $pseudonymized->keyId,
                'error' => $e->getMessage(),
            ]);
            
            throw AnonymizationException::dePseudonymizationFailed($e->getMessage());
        }
    }

    /**
     * @inheritDoc
     */
    public function generatePseudonym(string $data, string $context, string $keyId): string
    {
        $this->logger->debug('Generating pseudonym', [
            'context' => $context,
            'keyId' => $keyId,
        ]);

        try {
            $key = $this->keyStorage->retrieve($keyId);
            
            // Combine data and context with separator that cannot appear in either
            $combined = $context . "\x00" . $data;
            
            // Generate HMAC using the key material
            $pseudonym = hash_hmac('sha256', $combined, $key->getKeyBinary());
            
            $this->logger->debug('Pseudonym generated', [
                'context' => $context,
                'keyId' => $keyId,
            ]);
            
            return $pseudonym;
        } catch (\Throwable $e) {
            $this->logger->error('Pseudonym generation failed', [
                'context' => $context,
                'keyId' => $keyId,
                'error' => $e->getMessage(),
            ]);
            
            throw AnonymizationException::pseudonymizationFailed($e->getMessage());
        }
    }

    /**
     * @inheritDoc
     */
    public function verifyAnonymized(string $data, AnonymizedData $anonymized, array $options = []): bool
    {
        // Non-deterministic methods cannot be verified
        if (!$anonymized->isDeterministic()) {
            return false;
        }

        // For salted hash with stored salt, we cannot verify without the salt
        if ($anonymized->method === AnonymizationMethod::SALTED_HASH) {
            return false;
        }

        // Suppression destroys data - cannot verify original
        if ($anonymized->method === AnonymizationMethod::SUPPRESSION) {
            return false;
        }

        // Re-anonymize the data with same method and compare
        try {
            $reAnonymized = $this->anonymize($data, $anonymized->method, $options);
            
            return hash_equals($anonymized->anonymizedValue, $reAnonymized->anonymizedValue);
        } catch (\Throwable) {
            return false;
        }
    }

    // =====================================================
    // PRIVATE METHODS - Anonymization Strategies
    // =====================================================

    /**
     * Hash-based anonymization using SHA-256
     *
     * Simple hash without salt - deterministic and fast.
     * Vulnerable to rainbow table attacks for known input sets.
     */
    private function hashBasedAnonymize(string $data): string
    {
        $result = $this->hasher->hash($data, HashAlgorithm::SHA256);
        return $result->hash;
    }

    /**
     * Salted hash anonymization
     *
     * Generates random salt and prepends to hash output.
     * Non-deterministic - protects against rainbow tables.
     *
     * @param string|null $salt Output parameter for generated salt
     */
    private function saltedHashAnonymize(string $data, ?string &$salt): string
    {
        // Generate 16-byte random salt
        $saltBytes = random_bytes(16);
        $salt = bin2hex($saltBytes);
        
        // Hash data with salt prepended
        $saltedData = $saltBytes . $data;
        $result = $this->hasher->hash($saltedData, HashAlgorithm::SHA256);
        
        // Return salt:hash format for verification capability
        return $salt . ':' . $result->hash;
    }

    /**
     * HMAC-based anonymization
     *
     * Uses keyed hash for deterministic but protected anonymization.
     * Allows correlation by authorized parties with key access.
     *
     * @param array<string, mixed> $options Must contain 'keyId'
     */
    private function hmacBasedAnonymize(string $data, array $options): string
    {
        $keyId = $options['keyId'];
        $key = $this->keyStorage->retrieve($keyId);
        
        return hash_hmac('sha256', $data, $key->getKeyBinary());
    }

    /**
     * K-anonymity generalization
     *
     * Generalizes data according to provided hierarchy.
     * Example: Age 25 → "20-30", ZIP 12345 → "123**"
     *
     * @param array<string, mixed> $options Must contain 'hierarchy' with generalization rules
     */
    private function kAnonymityAnonymize(string $data, array $options): string
    {
        $hierarchy = $options['hierarchy'] ?? null;
        
        if (!is_array($hierarchy)) {
            throw AnonymizationException::invalidHierarchy('hierarchy must be an array');
        }

        // Check for exact match first
        if (isset($hierarchy[$data])) {
            return $hierarchy[$data];
        }

        // Check for range-based generalization (for numeric data)
        if (is_numeric($data)) {
            $numericValue = (float) $data;
            foreach ($hierarchy as $key => $generalized) {
                $keyStr = (string) $key;
                if (str_contains($keyStr, '-')) {
                    [$min, $max] = explode('-', $keyStr, 2);
                    if ($numericValue >= (float) $min && $numericValue <= (float) $max) {
                        return $generalized;
                    }
                }
            }
        }

        // Check for prefix-based generalization (e.g., ZIP codes)
        foreach ($hierarchy as $key => $generalized) {
            $keyStr = (string) $key;
            if (str_starts_with($data, rtrim($keyStr, '*'))) {
                return $generalized;
            }
        }

        // Return default generalization or the original data
        return $hierarchy['*'] ?? $hierarchy['default'] ?? '[GENERALIZED]';
    }

    /**
     * Suppression anonymization
     *
     * Completely removes data, replacing with suppression marker.
     * Maximum privacy - no residual information.
     */
    private function suppressionAnonymize(): string
    {
        return '[SUPPRESSED]';
    }

    // =====================================================
    // PRIVATE METHODS - Helpers
    // =====================================================

    /**
     * Validate required options for anonymization method
     *
     * @param array<string, mixed> $options
     * @throws AnonymizationException If required options are missing
     */
    private function validateOptions(AnonymizationMethod $method, array $options): void
    {
        foreach ($method->getRequiredOptions() as $requiredOption) {
            if (!isset($options[$requiredOption])) {
                throw AnonymizationException::missingOption($requiredOption, $method->value);
            }
        }
    }

    /**
     * Serialize encrypted data to a storable string format
     *
     * Format: base64(json({ciphertext, iv, tag, algorithm}))
     */
    private function serializeEncryptedData(EncryptedData $encrypted): string
    {
        $json = json_encode($encrypted->toArray(), JSON_THROW_ON_ERROR);
        return base64_encode($json);
    }

    /**
     * Deserialize encrypted data from stored string format
     */
    private function deserializeEncryptedData(string $serialized): EncryptedData
    {
        $decoded = base64_decode($serialized, true);
        if ($decoded === false) {
            throw AnonymizationException::dePseudonymizationFailed('Invalid base64 encoding');
        }
        
        try {
            $data = json_decode($decoded, true, 512, JSON_THROW_ON_ERROR);
            return EncryptedData::fromArray($data);
        } catch (\JsonException $e) {
            throw AnonymizationException::dePseudonymizationFailed('Invalid JSON format: ' . $e->getMessage());
        }
    }
}
