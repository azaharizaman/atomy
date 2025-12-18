<?php

declare(strict_types=1);

namespace Nexus\Crypto\Contracts;

use Nexus\Crypto\Enums\AnonymizationMethod;
use Nexus\Crypto\ValueObjects\AnonymizedData;
use Nexus\Crypto\ValueObjects\PseudonymizedData;

/**
 * Anonymizer Interface
 *
 * Provides data anonymization and pseudonymization for privacy compliance.
 *
 * Key Concepts:
 * - **Anonymization**: Irreversible transformation (cannot recover original data)
 *   - GDPR: Anonymized data is no longer personal data
 *   - Safe for analytics, reporting, and data retention
 *
 * - **Pseudonymization**: Reversible with key (can recover original with authorization)
 *   - GDPR: Pseudonymized data is still personal data
 *   - Useful for data processing with access control
 *
 * @see https://gdpr-info.eu/recitals/no-26/ GDPR definition of anonymization
 * @see https://gdpr-info.eu/art-4-gdpr/ GDPR Article 4(5) - Pseudonymization
 */
interface AnonymizerInterface
{
    /**
     * Anonymize data (irreversible)
     *
     * Transforms data into a form that cannot be reversed to obtain the original value.
     * The transformation is permanent - there is no way to recover the original data.
     *
     * Use cases:
     * - GDPR Right to Erasure compliance (anonymize instead of delete for audit)
     * - Statistical analysis on anonymized datasets
     * - Long-term data retention without privacy concerns
     *
     * @param string $data The sensitive data to anonymize
     * @param AnonymizationMethod $method Method to use (default: SALTED_HASH for security)
     * @param array<string, mixed> $options Method-specific options (e.g., 'keyId' for HMAC)
     * @return AnonymizedData Immutable result with method metadata
     * @throws \Nexus\Crypto\Exceptions\AnonymizationException On failure
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
     * The key must be managed separately from the pseudonymized data.
     *
     * Use cases:
     * - Data processing by third parties (share pseudonym, not identity)
     * - Access control (only key holders can de-pseudonymize)
     * - Research datasets with controlled re-identification capability
     *
     * @param string $data The sensitive data to pseudonymize
     * @param string $keyId Identifier of the encryption key to use
     * @return PseudonymizedData Encrypted result with key reference
     * @throws \Nexus\Crypto\Exceptions\AnonymizationException On encryption failure
     * @throws \Nexus\Crypto\Exceptions\InvalidKeyException If key not found
     */
    public function pseudonymize(string $data, string $keyId): PseudonymizedData;

    /**
     * De-pseudonymize data (reverse pseudonymization)
     *
     * Decrypts pseudonymized data to recover the original value.
     * Requires access to the key used during pseudonymization.
     *
     * Security considerations:
     * - Access to this operation should be strictly controlled
     * - All de-pseudonymization should be logged for audit
     * - Consider rate limiting to prevent bulk extraction
     *
     * @param PseudonymizedData $pseudonymized The pseudonymized data to decrypt
     * @return string The original plaintext data
     * @throws \Nexus\Crypto\Exceptions\AnonymizationException On decryption failure
     * @throws \Nexus\Crypto\Exceptions\InvalidKeyException If key not found or expired
     */
    public function dePseudonymize(PseudonymizedData $pseudonymized): string;

    /**
     * Generate consistent pseudonym for cross-system correlation
     *
     * Creates a deterministic pseudonym that can be used to correlate records
     * across systems without exposing the original data. Different contexts
     * produce different pseudonyms, preventing cross-context correlation.
     *
     * Use cases:
     * - Link patient records across hospitals (context = hospital pair)
     * - Match customer records in data warehouse (context = warehouse ID)
     * - Cross-reference anonymized audit logs (context = audit domain)
     *
     * @param string $data The data to generate pseudonym for
     * @param string $context Context identifier (prevents cross-context linking)
     * @param string $keyId Identifier of the HMAC key to use
     * @return string Deterministic pseudonym (hex-encoded HMAC)
     * @throws \Nexus\Crypto\Exceptions\AnonymizationException On failure
     * @throws \Nexus\Crypto\Exceptions\InvalidKeyException If key not found
     */
    public function generatePseudonym(string $data, string $context, string $keyId): string;

    /**
     * Verify if data matches an anonymized value (for deterministic methods only)
     *
     * Only works with deterministic anonymization methods (HASH_BASED, HMAC_BASED).
     * For non-deterministic methods, returns false.
     *
     * Use cases:
     * - Verify if a user's email matches an anonymized record
     * - Check if a transaction ID corresponds to an anonymized entry
     *
     * @param string $data The original data to verify
     * @param AnonymizedData $anonymized The anonymized data to compare against
     * @param array<string, mixed> $options Same options used during anonymization (e.g., keyId)
     * @return bool True if data matches, false otherwise
     */
    public function verifyAnonymized(string $data, AnonymizedData $anonymized, array $options = []): bool;
}
