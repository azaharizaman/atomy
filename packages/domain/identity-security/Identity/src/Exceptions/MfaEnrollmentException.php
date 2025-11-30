<?php

declare(strict_types=1);

namespace Nexus\Identity\Exceptions;

use RuntimeException;

/**
 * MFA Enrollment Exception
 *
 * Thrown when MFA enrollment operations fail due to business rule violations
 * or invalid state transitions.
 *
 * @package Nexus\Identity
 */
final class MfaEnrollmentException extends RuntimeException
{
    /**
     * User already has TOTP enrolled
     *
     * @param string $userId User identifier
     * @return self
     */
    public static function totpAlreadyEnrolled(string $userId): self
    {
        return new self("User {$userId} already has TOTP enrolled");
    }

    /**
     * Cannot revoke last authentication method
     *
     * @param string $userId User identifier
     * @return self
     */
    public static function cannotRevokeLastMethod(string $userId): self
    {
        return new self(
            "Cannot revoke last authentication method for user {$userId}. " .
            "User must have at least one authentication method or a password."
        );
    }

    /**
     * MFA enrollment not found
     *
     * @param string $enrollmentId Enrollment identifier
     * @return self
     */
    public static function enrollmentNotFound(string $enrollmentId): self
    {
        return new self("MFA enrollment {$enrollmentId} not found");
    }

    /**
     * WebAuthn credential not found
     *
     * @param string $credentialId Credential identifier
     * @return self
     */
    public static function credentialNotFound(string $credentialId): self
    {
        return new self("WebAuthn credential {$credentialId} not found");
    }

    /**
     * Invalid backup code count
     *
     * @param int $count Requested count
     * @param int $min Minimum allowed
     * @param int $max Maximum allowed
     * @return self
     */
    public static function invalidBackupCodeCount(int $count, int $min = 8, int $max = 20): self
    {
        return new self(
            "Backup code count {$count} is invalid. Must be between {$min} and {$max}."
        );
    }

    /**
     * User has no resident keys for passwordless mode
     *
     * @param string $userId User identifier
     * @return self
     */
    public static function noResidentKeysEnrolled(string $userId): self
    {
        return new self(
            "Cannot enable passwordless mode for user {$userId}. " .
            "User must have at least one resident key (discoverable credential) enrolled."
        );
    }

    /**
     * Friendly name is invalid
     *
     * @param string $name Provided name
     * @return self
     */
    public static function invalidFriendlyName(string $name): self
    {
        return new self(
            "Friendly name '{$name}' is invalid. Must be 1-100 characters."
        );
    }

    /**
     * User not authorized to perform operation
     *
     * @param string $userId User identifier
     * @param string $operation Operation name
     * @return self
     */
    public static function unauthorized(string $userId, string $operation): self
    {
        return new self("User {$userId} is not authorized to {$operation}");
    }

    /**
     * TOTP enrollment not verified
     *
     * @param string $userId User identifier
     * @return self
     */
    public static function totpNotVerified(string $userId): self
    {
        return new self(
            "TOTP enrollment for user {$userId} has not been verified. " .
            "User must verify TOTP code before enrollment is activated."
        );
    }

    /**
     * Generic enrollment failure
     *
     * @param string $message Error message
     * @return self
     */
    public static function enrollmentFailed(string $message): self
    {
        return new self("MFA enrollment failed: {$message}");
    }
}
