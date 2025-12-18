<?php

declare(strict_types=1);

namespace Nexus\Crypto\Enums;

/**
 * Anonymization Method Enum
 *
 * Defines available anonymization techniques for irreversible data protection.
 * Each method provides different trade-offs between privacy, utility, and performance.
 *
 * @see https://gdpr-info.eu/recitals/no-26/ GDPR definition of anonymization
 * @see https://www.enisa.europa.eu/publications/pseudonymisation-techniques-and-best-practices ENISA best practices
 */
enum AnonymizationMethod: string
{
    /**
     * Hash-based anonymization using SHA-256
     *
     * Properties:
     * - Deterministic: Same input always produces same output
     * - Fast: O(1) complexity
     * - Vulnerable to rainbow table attacks without salt
     *
     * Use case: Internal correlation where attacker cannot enumerate inputs
     */
    case HASH_BASED = 'hash_based';

    /**
     * Salted hash anonymization
     *
     * Properties:
     * - Non-deterministic: Same input produces different outputs (random salt)
     * - Protected against rainbow tables
     * - Cannot correlate records across runs
     *
     * Use case: Strong anonymization where cross-record correlation is not needed
     */
    case SALTED_HASH = 'salted_hash';

    /**
     * HMAC-based anonymization (keyed hash)
     *
     * Properties:
     * - Deterministic with key: Same input + key = same output
     * - Key-dependent: Different keys produce different outputs
     * - Enables controlled correlation (with key holder permission)
     *
     * Use case: Pseudonymization that needs to be correlatable by authorized parties
     */
    case HMAC_BASED = 'hmac_based';

    /**
     * K-anonymity generalization
     *
     * Properties:
     * - Groups data into equivalence classes
     * - Requires pre-defined generalization hierarchy
     * - Preserves some data utility (age 25 → "20-30")
     *
     * Use case: Statistical analysis requiring grouped/generalized data
     */
    case K_ANONYMITY = 'k_anonymity';

    /**
     * Data suppression (complete removal)
     *
     * Properties:
     * - Maximum privacy: No residual information
     * - Zero utility: Data cannot be used for any purpose
     * - Compliant with GDPR right to erasure
     *
     * Use case: Complete data erasure for regulatory compliance
     */
    case SUPPRESSION = 'suppression';

    /**
     * Check if method is deterministic (same input = same output)
     *
     * Deterministic methods allow correlation of anonymized records.
     * Non-deterministic methods prevent such correlation.
     */
    public function isDeterministic(): bool
    {
        return match ($this) {
            self::HASH_BASED => true,
            self::HMAC_BASED => true,
            self::SALTED_HASH => false,
            self::K_ANONYMITY => true,
            self::SUPPRESSION => true,
        };
    }

    /**
     * Check if method requires a key
     *
     * HMAC-based anonymization requires a secret key for computation.
     */
    public function requiresKey(): bool
    {
        return $this === self::HMAC_BASED;
    }

    /**
     * Check if method requires additional options
     *
     * K-anonymity requires a generalization hierarchy.
     * HMAC requires a key ID.
     */
    public function requiresOptions(): bool
    {
        return match ($this) {
            self::HMAC_BASED => true,
            self::K_ANONYMITY => true,
            default => false,
        };
    }

    /**
     * Get required option keys for this method
     *
     * @return array<string>
     */
    public function getRequiredOptions(): array
    {
        return match ($this) {
            self::HMAC_BASED => ['keyId'],
            self::K_ANONYMITY => ['hierarchy'],
            default => [],
        };
    }

    /**
     * Get description for documentation/logging
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::HASH_BASED => 'SHA-256 hash without salt (deterministic, fast)',
            self::SALTED_HASH => 'SHA-256 hash with random salt (non-deterministic)',
            self::HMAC_BASED => 'HMAC-SHA256 with secret key (deterministic, correlatable with key)',
            self::K_ANONYMITY => 'Generalization to equivalence class (preserves utility)',
            self::SUPPRESSION => 'Complete data suppression (maximum privacy)',
        };
    }

    /**
     * Get security level classification
     *
     * @return string 'high' | 'medium' | 'low'
     */
    public function getSecurityLevel(): string
    {
        return match ($this) {
            self::SUPPRESSION => 'high',
            self::SALTED_HASH => 'high',
            self::HMAC_BASED => 'medium',
            self::HASH_BASED => 'low',
            self::K_ANONYMITY => 'medium',
        };
    }
}
