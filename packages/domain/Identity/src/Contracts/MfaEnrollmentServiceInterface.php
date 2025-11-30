<?php

declare(strict_types=1);

namespace Nexus\Domain\Identity\Contracts;

use Nexus\Domain\Identity\ValueObjects\BackupCodeSet;
use Nexus\Domain\Identity\ValueObjects\TotpSecret;
use Nexus\Domain\Identity\ValueObjects\WebAuthnCredential;
use Nexus\Domain\Identity\ValueObjects\WebAuthnRegistrationOptions;

/**
 * MFA Enrollment Service Interface
 *
 * Provides contract for enrolling and managing multi-factor authentication methods.
 * Coordinates between TOTP, WebAuthn, and backup code enrollment while enforcing
 * business rules and security policies.
 *
 * @package Nexus\Domain\Identity
 */
interface MfaEnrollmentServiceInterface
{
    /**
     * Enroll user in TOTP-based MFA
     *
     * Generates a new TOTP secret, stores it encrypted, and returns the secret
     * with QR code URI for authenticator app setup.
     *
     * @param string $userId User identifier (ULID)
     * @param string|null $issuer Optional issuer name (defaults to app name)
     * @param string|null $accountName Optional account name (defaults to user email)
     * @return array{secret: TotpSecret, qrCodeUri: string, qrCodeDataUrl: string}
     * @throws \Nexus\Domain\Identity\Exceptions\MfaEnrollmentException If user already has TOTP enrolled
     */
    public function enrollTotp(
        string $userId,
        ?string $issuer = null,
        ?string $accountName = null
    ): array;

    /**
     * Verify TOTP code during enrollment
     *
     * Validates the TOTP code provided by user during enrollment to ensure
     * authenticator app is correctly configured before activation.
     *
     * @param string $userId User identifier (ULID)
     * @param string $code 6-8 digit TOTP code
     * @return bool True if code is valid and enrollment activated
     * @throws \Nexus\Domain\Identity\Exceptions\MfaVerificationException If code is invalid
     */
    public function verifyTotpEnrollment(string $userId, string $code): bool;

    /**
     * Generate WebAuthn registration options
     *
     * Creates PublicKeyCredentialCreationOptions for WebAuthn credential registration.
     * Supports both platform (Touch ID, Face ID) and cross-platform (YubiKey) authenticators.
     *
     * @param string $userId User identifier (ULID)
     * @param string $userName User's name for credential display
     * @param string $userDisplayName User's display name for credential
     * @param bool $requireResidentKey True for passwordless (discoverable credentials)
     * @param bool $requirePlatformAuthenticator True to require built-in authenticator
     * @return WebAuthnRegistrationOptions Immutable registration options
     */
    public function generateWebAuthnRegistrationOptions(
        string $userId,
        string $userName,
        string $userDisplayName,
        bool $requireResidentKey = false,
        bool $requirePlatformAuthenticator = false
    ): WebAuthnRegistrationOptions;

    /**
     * Complete WebAuthn registration
     *
     * Verifies the attestation response from authenticator and stores the credential.
     * Validates challenge, origin, and attestation format.
     *
     * @param string $userId User identifier (ULID)
     * @param string $attestationResponseJson JSON-encoded PublicKeyCredential from browser
     * @param string $expectedChallenge Challenge that was sent to client (base64)
     * @param string $expectedOrigin Expected origin (https://example.com)
     * @param string|null $friendlyName Optional friendly name for credential
     * @return WebAuthnCredential Verified and stored credential
     * @throws \Nexus\Domain\Identity\Exceptions\WebAuthnVerificationException If verification fails
     */
    public function completeWebAuthnRegistration(
        string $userId,
        string $attestationResponseJson,
        string $expectedChallenge,
        string $expectedOrigin,
        ?string $friendlyName = null
    ): WebAuthnCredential;

    /**
     * Generate backup codes
     *
     * Creates a new set of one-time recovery codes for account recovery.
     * Automatically revokes any existing backup codes.
     *
     * @param string $userId User identifier (ULID)
     * @param int $count Number of codes to generate (default: 10)
     * @return BackupCodeSet Immutable set of backup codes with hashed values
     * @throws \Nexus\Domain\Identity\Exceptions\MfaEnrollmentException If count is invalid
     */
    public function generateBackupCodes(string $userId, int $count = 10): BackupCodeSet;

    /**
     * Revoke MFA method
     *
     * Disables a specific MFA enrollment for a user.
     * Cannot revoke the last enrolled method unless user has password.
     *
     * @param string $userId User identifier (ULID)
     * @param string $enrollmentId MFA enrollment identifier (ULID)
     * @return bool True if revocation successful
     * @throws \Nexus\Domain\Identity\Exceptions\MfaEnrollmentException If last method or not found
     */
    public function revokeEnrollment(string $userId, string $enrollmentId): bool;

    /**
     * Revoke WebAuthn credential
     *
     * Removes a specific WebAuthn credential (passkey/security key).
     * Ensures user retains at least one authentication method.
     *
     * @param string $userId User identifier (ULID)
     * @param string $credentialId Base64URL-encoded credential ID
     * @return bool True if revocation successful
     * @throws \Nexus\Domain\Identity\Exceptions\MfaEnrollmentException If last credential
     */
    public function revokeWebAuthnCredential(string $userId, string $credentialId): bool;

    /**
     * Update WebAuthn credential friendly name
     *
     * Allows user to rename a credential for easier identification.
     *
     * @param string $userId User identifier (ULID)
     * @param string $credentialId Base64URL-encoded credential ID
     * @param string $friendlyName New friendly name (1-100 characters)
     * @return bool True if update successful
     * @throws \Nexus\Domain\Identity\Exceptions\MfaEnrollmentException If credential not found
     */
    public function updateWebAuthnCredentialName(
        string $userId,
        string $credentialId,
        string $friendlyName
    ): bool;

    /**
     * Get user's enrolled MFA methods
     *
     * Returns all active MFA enrollments for a user.
     *
     * @param string $userId User identifier (ULID)
     * @return array Array of enrollment data with method, enrolled_at, last_used_at
     */
    public function getUserEnrollments(string $userId): array;

    /**
     * Get user's WebAuthn credentials
     *
     * Returns all registered WebAuthn credentials (passkeys/security keys).
     *
     * @param string $userId User identifier (ULID)
     * @return WebAuthnCredential[] Array of credentials
     */
    public function getUserWebAuthnCredentials(string $userId): array;

    /**
     * Check if user has enrolled MFA
     *
     * @param string $userId User identifier (ULID)
     * @return bool True if user has at least one active MFA method
     */
    public function hasEnrolledMfa(string $userId): bool;

    /**
     * Check if user has specific MFA method enrolled
     *
     * @param string $userId User identifier (ULID)
     * @param string $method MFA method (totp, passkey, backup_codes)
     * @return bool True if method is enrolled
     */
    public function hasMethodEnrolled(string $userId, string $method): bool;

    /**
     * Enable passwordless mode for user
     *
     * Converts user account to passwordless by requiring at least one
     * resident key (discoverable credential). Requires user confirmation.
     *
     * @param string $userId User identifier (ULID)
     * @return bool True if conversion successful
     * @throws \Nexus\Domain\Identity\Exceptions\MfaEnrollmentException If no resident keys enrolled
     */
    public function enablePasswordlessMode(string $userId): bool;

    /**
     * Admin: Reset user MFA
     *
     * Emergency reset of all MFA methods for a user.
     * Generates recovery token with 6-hour TTL.
     * Logs action to audit trail.
     *
     * @param string $userId User identifier (ULID)
     * @param string $adminUserId Admin performing reset (ULID)
     * @param string $reason Reason for reset
     * @return string Recovery token for user to re-enroll
     * @throws \Nexus\Domain\Identity\Exceptions\UnauthorizedException If admin lacks permission
     */
    public function adminResetMfa(
        string $userId,
        string $adminUserId,
        string $reason
    ): string;
}
