<?php

declare(strict_types=1);

namespace Nexus\Domain\Identity\Contracts;

use Nexus\Domain\Identity\ValueObjects\WebAuthnAuthenticationOptions;

/**
 * MFA Verification Service Interface
 *
 * Provides contract for verifying multi-factor authentication challenges.
 * Implements rate limiting, fallback chains, and security policies for
 * TOTP, WebAuthn, and backup code verification.
 *
 * @package Nexus\Domain\Identity
 */
interface MfaVerificationServiceInterface
{
    /**
     * Verify TOTP code
     *
     * Validates a time-based one-time password code.
     * Implements rate limiting (5 attempts per 15 minutes).
     * Uses ±1 time window (30 second drift tolerance).
     *
     * @param string $userId User identifier (ULID)
     * @param string $code 6-8 digit TOTP code
     * @return bool True if code is valid
     * @throws \Nexus\Domain\Identity\Exceptions\MfaVerificationException If code invalid or rate limited
     */
    public function verifyTotp(string $userId, string $code): bool;

    /**
     * Generate WebAuthn authentication options
     *
     * Creates PublicKeyCredentialRequestOptions for WebAuthn authentication.
     * Supports both user-specific and usernameless (discoverable credentials) flows.
     *
     * @param string|null $userId User identifier (ULID) - null for usernameless
     * @param bool $requireUserVerification True to require biometric/PIN
     * @return WebAuthnAuthenticationOptions Immutable authentication options
     */
    public function generateWebAuthnAuthenticationOptions(
        ?string $userId = null,
        bool $requireUserVerification = false
    ): WebAuthnAuthenticationOptions;

    /**
     * Verify WebAuthn assertion
     *
     * Validates the assertion response from authenticator.
     * Verifies challenge, origin, signature, and sign count.
     * Updates credential sign count and last used timestamp.
     *
     * @param string $assertionResponseJson JSON-encoded PublicKeyCredential from browser
     * @param string $expectedChallenge Challenge that was sent to client (base64)
     * @param string $expectedOrigin Expected origin (https://example.com)
     * @param string|null $userId Expected user ID (null for usernameless flow)
     * @return array{userId: string, credentialId: string} User and credential identifiers
     * @throws \Nexus\Domain\Identity\Exceptions\WebAuthnVerificationException If verification fails
     * @throws \Nexus\Domain\Identity\Exceptions\SignCountRollbackException If sign count rollback detected
     */
    public function verifyWebAuthn(
        string $assertionResponseJson,
        string $expectedChallenge,
        string $expectedOrigin,
        ?string $userId = null
    ): array;

    /**
     * Verify backup code
     *
     * Validates and consumes a one-time backup code.
     * Uses constant-time comparison to prevent timing attacks.
     * Marks code as consumed to prevent reuse.
     *
     * @param string $userId User identifier (ULID)
     * @param string $code Backup code (format: XXXX-XXXX-XX)
     * @return bool True if code is valid and consumed
     * @throws \Nexus\Domain\Identity\Exceptions\MfaVerificationException If code invalid or already consumed
     */
    public function verifyBackupCode(string $userId, string $code): bool;

    /**
     * Verify with fallback chain
     *
     * Attempts verification with primary method, falls back to alternatives.
     * Implements exponential backoff for failed attempts.
     *
     * Example chain: TOTP → Backup Code → Admin Recovery
     *
     * @param string $userId User identifier (ULID)
     * @param array $credentials Array of credentials to try (method => code)
     * @return array{method: string, verified: bool} Verification result with method used
     * @throws \Nexus\Domain\Identity\Exceptions\MfaVerificationException If all methods fail
     */
    public function verifyWithFallback(string $userId, array $credentials): array;

    /**
     * Check if user is rate limited
     *
     * Returns whether user has exceeded verification attempts.
     *
     * @param string $userId User identifier (ULID)
     * @param string $method MFA method (totp, webauthn, backup_code)
     * @return bool True if user is currently rate limited
     */
    public function isRateLimited(string $userId, string $method): bool;

    /**
     * Get remaining backup codes count
     *
     * Returns number of unused backup codes for regeneration threshold detection.
     *
     * @param string $userId User identifier (ULID)
     * @return int Number of remaining (unconsumed) backup codes
     */
    public function getRemainingBackupCodesCount(string $userId): int;

    /**
     * Check if backup codes need regeneration
     *
     * Returns true if remaining codes are below threshold (≤2).
     *
     * @param string $userId User identifier (ULID)
     * @return bool True if regeneration recommended
     */
    public function shouldRegenerateBackupCodes(string $userId): bool;

    /**
     * Record verification attempt
     *
     * Logs verification attempt for rate limiting and audit trail.
     *
     * @param string $userId User identifier (ULID)
     * @param string $method MFA method attempted
     * @param bool $success Whether attempt was successful
     * @param string|null $ipAddress Optional IP address
     * @param string|null $userAgent Optional user agent
     * @return void
     */
    public function recordVerificationAttempt(
        string $userId,
        string $method,
        bool $success,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): void;

    /**
     * Clear rate limit for user
     *
     * Admin function to reset rate limiting state.
     *
     * @param string $userId User identifier (ULID)
     * @param string $method MFA method to clear
     * @return bool True if cleared successfully
     */
    public function clearRateLimit(string $userId, string $method): bool;
}
