<?php

declare(strict_types=1);

namespace App\Services\Identity;

use Nexus\Identity\Contracts\MfaEnrollmentServiceInterface;
use Nexus\Identity\Exceptions\MfaEnrollmentException;
use Nexus\Identity\ValueObjects\BackupCodeSet;
use Nexus\Identity\ValueObjects\WebAuthnCredential;
use Nexus\Identity\ValueObjects\WebAuthnRegistrationOptions;

/**
 * MFA is not implemented for Atomy-Q; satisfies Nexus Identity DI until enrollment is productized.
 */
final class AtomyNoopMfaEnrollmentService implements MfaEnrollmentServiceInterface
{
    public function enrollTotp(string $userId, ?string $issuer = null, ?string $accountName = null): array
    {
        throw new MfaEnrollmentException('MFA enrollment is not enabled for Atomy-Q');
    }

    public function verifyTotpEnrollment(string $userId, string $code): bool
    {
        return false;
    }

    public function generateWebAuthnRegistrationOptions(
        string $userId,
        string $userName,
        string $userDisplayName,
        bool $requireResidentKey = false,
        bool $requirePlatformAuthenticator = false,
    ): WebAuthnRegistrationOptions {
        throw new MfaEnrollmentException('WebAuthn is not enabled for Atomy-Q');
    }

    public function completeWebAuthnRegistration(
        string $userId,
        string $attestationResponseJson,
        string $expectedChallenge,
        string $expectedOrigin,
        ?string $friendlyName = null,
    ): WebAuthnCredential {
        throw new MfaEnrollmentException('WebAuthn is not enabled for Atomy-Q');
    }

    public function generateBackupCodes(string $userId, int $count = 10): BackupCodeSet
    {
        throw new MfaEnrollmentException('MFA backup codes are not enabled for Atomy-Q');
    }

    public function revokeEnrollment(string $userId, string $enrollmentId): bool
    {
        return false;
    }

    public function revokeWebAuthnCredential(string $userId, string $credentialId): bool
    {
        return false;
    }

    public function updateWebAuthnCredentialName(string $userId, string $credentialId, string $friendlyName): bool
    {
        return false;
    }

    public function getUserEnrollments(string $userId): array
    {
        return [];
    }

    public function getUserWebAuthnCredentials(string $userId): array
    {
        return [];
    }

    public function hasEnrolledMfa(string $userId): bool
    {
        return false;
    }

    public function hasMethodEnrolled(string $userId, string $method): bool
    {
        return false;
    }

    public function enablePasswordlessMode(string $userId): bool
    {
        throw new MfaEnrollmentException('Passwordless mode is not enabled for Atomy-Q');
    }

    public function adminResetMfa(string $userId, string $adminUserId, string $reason): string
    {
        throw new MfaEnrollmentException('Admin MFA reset is not enabled for Atomy-Q');
    }
}
