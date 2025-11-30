<?php

declare(strict_types=1);

namespace Nexus\Domain\Identity\Exceptions;

use RuntimeException;

/**
 * MFA Verification Exception
 *
 * Thrown when MFA verification fails due to invalid codes, rate limiting,
 * or security policy violations.
 *
 * @package Nexus\Domain\Identity
 */
final class MfaVerificationException extends RuntimeException
{
    /**
     * Invalid TOTP code
     *
     * @param string $userId User identifier
     * @return self
     */
    public static function invalidTotpCode(string $userId): self
    {
        return new self("Invalid TOTP code for user {$userId}");
    }

    /**
     * Invalid backup code
     *
     * @param string $userId User identifier
     * @return self
     */
    public static function invalidBackupCode(string $userId): self
    {
        return new self("Invalid backup code for user {$userId}");
    }

    /**
     * Backup code already consumed
     *
     * @param string $code Backup code
     * @return self
     */
    public static function backupCodeAlreadyConsumed(string $code): self
    {
        return new self("Backup code {$code} has already been consumed");
    }

    /**
     * User is rate limited
     *
     * @param string $userId User identifier
     * @param string $method MFA method
     * @param int $retryAfterSeconds Seconds until retry allowed
     * @return self
     */
    public static function rateLimited(string $userId, string $method, int $retryAfterSeconds): self
    {
        return new self(
            "User {$userId} is rate limited for {$method} verification. " .
            "Retry after {$retryAfterSeconds} seconds."
        );
    }

    /**
     * No MFA method enrolled
     *
     * @param string $userId User identifier
     * @return self
     */
    public static function noMethodEnrolled(string $userId): self
    {
        return new self("User {$userId} has no MFA methods enrolled");
    }

    /**
     * MFA method not enrolled
     *
     * @param string $userId User identifier
     * @param string $method MFA method
     * @return self
     */
    public static function methodNotEnrolled(string $userId, string $method): self
    {
        return new self("User {$userId} does not have {$method} enrolled");
    }

    /**
     * All verification methods failed
     *
     * @param string $userId User identifier
     * @return self
     */
    public static function allMethodsFailed(string $userId): self
    {
        return new self(
            "All verification methods failed for user {$userId}. " .
            "Contact administrator for account recovery."
        );
    }

    /**
     * Invalid verification code format
     *
     * @param string $code Provided code
     * @param string $expectedFormat Expected format description
     * @return self
     */
    public static function invalidCodeFormat(string $code, string $expectedFormat): self
    {
        return new self(
            "Invalid code format. Expected: {$expectedFormat}"
        );
    }

    /**
     * No backup codes remaining
     *
     * @param string $userId User identifier
     * @return self
     */
    public static function noBackupCodesRemaining(string $userId): self
    {
        return new self(
            "User {$userId} has no backup codes remaining. Generate new codes."
        );
    }

    /**
     * Generic verification failure
     *
     * @param string $message Error message
     * @return self
     */
    public static function verificationFailed(string $message): self
    {
        return new self("MFA verification failed: {$message}");
    }
}
