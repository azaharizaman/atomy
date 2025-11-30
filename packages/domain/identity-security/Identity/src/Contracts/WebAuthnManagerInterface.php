<?php

declare(strict_types=1);

namespace Nexus\Identity\Contracts;

use Nexus\Identity\ValueObjects\WebAuthnAuthenticationOptions;
use Nexus\Identity\ValueObjects\WebAuthnCredential;
use Nexus\Identity\ValueObjects\WebAuthnRegistrationOptions;

/**
 * WebAuthn Manager Interface
 *
 * Handles WebAuthn/FIDO2 registration and authentication operations.
 */
interface WebAuthnManagerInterface
{
    /**
     * Generate registration options for WebAuthn credential creation
     *
     * @param string $userId User identifier
     * @param string $userName User name (email)
     * @param string $userDisplayName User display name
     * @param array<string> $excludeCredentialIds Credential IDs to exclude (already registered)
     * @param bool $requireResidentKey Require discoverable credential (passkey)
     * @param bool $requirePlatformAuthenticator Require platform authenticator (Touch ID, Face ID)
     * @return WebAuthnRegistrationOptions
     */
    public function generateRegistrationOptions(
        string $userId,
        string $userName,
        string $userDisplayName,
        array $excludeCredentialIds = [],
        bool $requireResidentKey = false,
        bool $requirePlatformAuthenticator = false
    ): WebAuthnRegistrationOptions;

    /**
     * Verify registration response and extract credential
     *
     * @param string $credentialJson JSON response from navigator.credentials.create()
     * @param string $expectedChallenge Expected challenge (base64url-encoded)
     * @param string $expectedOrigin Expected origin (https://example.com)
     * @return WebAuthnCredential Verified credential
     * @throws \Nexus\Identity\Exceptions\WebAuthnVerificationException
     */
    public function verifyRegistration(
        string $credentialJson,
        string $expectedChallenge,
        string $expectedOrigin
    ): WebAuthnCredential;

    /**
     * Generate authentication options for WebAuthn assertion
     *
     * @param array<string> $allowCredentialIds Allowed credential IDs (empty for usernameless)
     * @param bool $requireUserVerification Require user verification
     * @return WebAuthnAuthenticationOptions
     */
    public function generateAuthenticationOptions(
        array $allowCredentialIds = [],
        bool $requireUserVerification = false
    ): WebAuthnAuthenticationOptions;

    /**
     * Verify authentication response
     *
     * @param string $assertionJson JSON response from navigator.credentials.get()
     * @param string $expectedChallenge Expected challenge (base64url-encoded)
     * @param string $expectedOrigin Expected origin (https://example.com)
     * @param WebAuthnCredential $storedCredential Stored credential to verify against
     * @return array{credentialId: string, newSignCount: int, userHandle: string|null} Verification result
     * @throws \Nexus\Identity\Exceptions\WebAuthnVerificationException
     * @throws \Nexus\Identity\Exceptions\SignCountRollbackException
     */
    public function verifyAuthentication(
        string $assertionJson,
        string $expectedChallenge,
        string $expectedOrigin,
        WebAuthnCredential $storedCredential
    ): array;
}
