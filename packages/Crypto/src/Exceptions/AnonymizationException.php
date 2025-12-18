<?php

declare(strict_types=1);

namespace Nexus\Crypto\Exceptions;

/**
 * Anonymization Exception
 *
 * Thrown when anonymization or pseudonymization operations fail.
 * Provides factory methods for common failure scenarios.
 */
class AnonymizationException extends CryptoException
{
    /**
     * Create exception for invalid anonymization method
     */
    public static function invalidMethod(string $method): self
    {
        return new self(
            "Invalid anonymization method: '{$method}'. Use AnonymizationMethod enum values."
        );
    }

    /**
     * Create exception for missing required option
     */
    public static function missingOption(string $option, string $method): self
    {
        return new self(
            "Anonymization method '{$method}' requires '{$option}' option to be provided."
        );
    }

    /**
     * Create exception for pseudonymization failure
     */
    public static function pseudonymizationFailed(string $reason): self
    {
        return new self("Pseudonymization failed: {$reason}");
    }

    /**
     * Create exception for de-pseudonymization failure
     */
    public static function dePseudonymizationFailed(string $reason): self
    {
        return new self("De-pseudonymization failed: {$reason}");
    }

    /**
     * Create exception for non-deterministic method verification
     */
    public static function cannotVerifyNonDeterministic(string $method): self
    {
        return new self(
            "Cannot verify data against anonymization with non-deterministic method '{$method}'. " .
            "Only HASH_BASED and HMAC_BASED methods support verification."
        );
    }

    /**
     * Create exception for invalid generalization hierarchy
     */
    public static function invalidHierarchy(string $reason): self
    {
        return new self("Invalid generalization hierarchy for k-anonymity: {$reason}");
    }

    /**
     * Create exception for encryption/decryption failure during pseudonymization
     */
    public static function encryptionFailed(string $operation, string $reason): self
    {
        return new self("Encryption {$operation} failed during pseudonymization: {$reason}");
    }
}
