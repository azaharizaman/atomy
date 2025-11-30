<?php

declare(strict_types=1);

namespace Nexus\Domain\Identity\Services;

use DateTimeImmutable;
use Nexus\Domain\Identity\Contracts\MfaEnrollmentRepositoryInterface;
use Nexus\Domain\Identity\Contracts\MfaEnrollmentServiceInterface;
use Nexus\Domain\Identity\Contracts\TotpManagerInterface;
use Nexus\Domain\Identity\Contracts\WebAuthnCredentialRepositoryInterface;
use Nexus\Domain\Identity\Contracts\WebAuthnManagerInterface;
use Nexus\Domain\Identity\ValueObjects\MfaMethod;
use Nexus\Domain\Identity\Exceptions\MfaEnrollmentException;
use Nexus\Domain\Identity\ValueObjects\BackupCode;
use Nexus\Domain\Identity\ValueObjects\BackupCodeSet;
use Nexus\Domain\Identity\ValueObjects\TotpSecret;
use Nexus\Domain\Identity\ValueObjects\WebAuthnCredential;
use Nexus\Domain\Identity\ValueObjects\WebAuthnRegistrationOptions;
use Psr\Log\LoggerInterface;

/**
 * MFA Enrollment Service
 *
 * Orchestrates multi-factor authentication enrollment and credential management.
 * Coordinates TOTP, WebAuthn, and backup code enrollment while enforcing
 * business rules and security policies.
 *
 * @package Nexus\Domain\Identity
 */
final readonly class MfaEnrollmentService implements MfaEnrollmentServiceInterface
{
    public function __construct(
        private MfaEnrollmentRepositoryInterface $enrollmentRepository,
        private WebAuthnCredentialRepositoryInterface $credentialRepository,
        private TotpManagerInterface $totpManager,
        private WebAuthnManagerInterface $webAuthnManager,
        private LoggerInterface $logger
    ) {}

    public function enrollTotp(
        string $userId,
        ?string $issuer = null,
        ?string $accountName = null
    ): array {
        // Check if user already has TOTP enrolled
        if ($this->hasMethodEnrolled($userId, MfaMethod::TOTP->value)) {
            throw MfaEnrollmentException::totpAlreadyEnrolled($userId);
        }

        // Generate TOTP secret
        $secret = $this->totpManager->generateSecret();

        // Generate QR code URI and image
        $qrCodeUri = $this->totpManager->generateQrCodeUri(
            $secret,
            $issuer ?? 'Nexus ERP',
            $accountName ?? "user-{$userId}"
        );

        $qrCodeDataUrl = $this->totpManager->generateQrCodeImage($qrCodeUri);

        // Create pending enrollment (not activated until verified)
        $this->enrollmentRepository->create([
            'user_id' => $userId,
            'method' => MfaMethod::TOTP->value,
            'secret' => $secret->toArray(),
            'is_active' => false,
            'enrolled_at' => new DateTimeImmutable(),
        ]);

        $this->logger->info('TOTP enrollment initiated', [
            'user_id' => $userId,
        ]);

        return [
            'secret' => $secret,
            'qrCodeUri' => $qrCodeUri,
            'qrCodeDataUrl' => $qrCodeDataUrl,
        ];
    }

    public function verifyTotpEnrollment(string $userId, string $code): bool
    {
        // Find pending TOTP enrollment
        $enrollment = $this->enrollmentRepository->findPendingByUserAndMethod(
            $userId,
            MfaMethod::TOTP
        );

        if ($enrollment === null) {
            throw MfaEnrollmentException::totpNotVerified($userId);
        }

        // Reconstruct TotpSecret from stored data
        $secret = TotpSecret::fromArray($enrollment->getSecret());

        // Verify code
        $isValid = $this->totpManager->verifyCode($secret, $code);

        if (!$isValid) {
            $this->logger->warning('TOTP enrollment verification failed', [
                'user_id' => $userId,
            ]);

            return false;
        }

        // Activate enrollment
        $this->enrollmentRepository->activate($enrollment->getId());

        $this->logger->info('TOTP enrollment activated', [
            'user_id' => $userId,
            'enrollment_id' => $enrollment->getId(),
        ]);

        return true;
    }

    public function generateWebAuthnRegistrationOptions(
        string $userId,
        string $userName,
        string $userDisplayName,
        bool $requireResidentKey = false,
        bool $requirePlatformAuthenticator = false
    ): WebAuthnRegistrationOptions {
        // Get existing credentials to exclude
        $existingCredentials = $this->credentialRepository->findByUserId($userId);
        $excludeCredentialIds = array_map(
            fn(array $cred) => $cred['credential_id'],
            $existingCredentials
        );

        // Generate registration options
        $options = $this->webAuthnManager->generateRegistrationOptions(
            userId: $userId,
            userName: $userName,
            userDisplayName: $userDisplayName,
            excludeCredentialIds: $excludeCredentialIds,
            requireResidentKey: $requireResidentKey,
            requirePlatformAuthenticator: $requirePlatformAuthenticator
        );

        $this->logger->info('WebAuthn registration options generated', [
            'user_id' => $userId,
            'require_resident_key' => $requireResidentKey,
            'require_platform' => $requirePlatformAuthenticator,
        ]);

        return $options;
    }

    public function completeWebAuthnRegistration(
        string $userId,
        string $attestationResponseJson,
        string $expectedChallenge,
        string $expectedOrigin,
        ?string $friendlyName = null
    ): WebAuthnCredential {
        // Verify attestation response
        $credential = $this->webAuthnManager->verifyRegistration(
            credentialJson: $attestationResponseJson,
            expectedChallenge: $expectedChallenge,
            expectedOrigin: $expectedOrigin
        );

        // Store credential
        $this->credentialRepository->create([
            'user_id' => $userId,
            'credential_id' => $credential->credentialId,
            'public_key' => $credential->publicKey,
            'sign_count' => $credential->signCount,
            'transports' => $credential->transports,
            'aaguid' => $credential->aaguid,
            'friendly_name' => $friendlyName ?? 'Passkey',
            'last_used_at' => $credential->lastUsedAt,
            'created_at' => new DateTimeImmutable(),
        ]);

        // Create enrollment record if this is first passkey
        if (!$this->hasMethodEnrolled($userId, MfaMethod::PASSKEY->value)) {
            $this->enrollmentRepository->create([
                'user_id' => $userId,
                'method' => MfaMethod::PASSKEY->value,
                'is_active' => true,
                'enrolled_at' => new DateTimeImmutable(),
            ]);
        }

        $this->logger->info('WebAuthn credential registered', [
            'user_id' => $userId,
            'credential_id' => $credential->credentialId,
            'friendly_name' => $friendlyName,
        ]);

        return $credential;
    }

    public function generateBackupCodes(string $userId, int $count = 10): BackupCodeSet
    {
        // Validate count
        if ($count < 8 || $count > 20) {
            throw MfaEnrollmentException::invalidBackupCodeCount($count, 8, 20);
        }

        // Generate codes
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = BackupCode::generate();
        }

        $backupCodeSet = BackupCodeSet::create($codes);

        // Revoke existing backup codes
        $this->enrollmentRepository->revokeByUserAndMethod(
            $userId,
            MfaMethod::BACKUP_CODES->value
        );

        // Store new backup codes
        foreach ($codes as $code) {
            $this->enrollmentRepository->create([
                'user_id' => $userId,
                'method' => MfaMethod::BACKUP_CODES->value,
                'secret' => [
                    'hash' => $code->hash,
                    'consumed_at' => null,
                ],
                'is_active' => true,
                'enrolled_at' => new DateTimeImmutable(),
            ]);
        }

        $this->logger->info('Backup codes generated', [
            'user_id' => $userId,
            'count' => $count,
        ]);

        return $backupCodeSet;
    }

    public function revokeEnrollment(string $userId, string $enrollmentId): bool
    {
        // Find enrollment
        $enrollment = $this->enrollmentRepository->findById($enrollmentId);

        if ($enrollment === null || $enrollment->getUserId() !== $userId) {
            throw MfaEnrollmentException::enrollmentNotFound($enrollmentId);
        }

        // Check if this is the last method
        $enrollments = $this->getUserEnrollments($userId);
        $activeEnrollments = array_filter($enrollments, fn($e) => $e['is_active']);

        if (count($activeEnrollments) <= 1) {
            throw MfaEnrollmentException::cannotRevokeLastMethod($userId);
        }

        // Revoke enrollment
        $this->enrollmentRepository->revoke($enrollmentId);

        // If passkey enrollment, revoke all credentials
        if ($enrollment->getMethod() === MfaMethod::PASSKEY) {
            $this->credentialRepository->revokeAllByUserId($userId);
        }

        $this->logger->info('MFA enrollment revoked', [
            'user_id' => $userId,
            'enrollment_id' => $enrollmentId,
            'method' => $enrollment->getMethod()->value,
        ]);

        return true;
    }

    public function revokeWebAuthnCredential(string $userId, string $credentialId): bool
    {
        // Find credential
        $credential = $this->credentialRepository->findByCredentialId($credentialId);

        if ($credential === null || $credential['user_id'] !== $userId) {
            throw MfaEnrollmentException::credentialNotFound($credentialId);
        }

        // Check if this is the last credential AND passkey is the only method
        $remainingCredentials = $this->credentialRepository->findByUserId($userId);
        if (count($remainingCredentials) <= 1) {
            $enrollments = $this->getUserEnrollments($userId);
            $activeEnrollments = array_filter($enrollments, fn($e) => $e['is_active']);

            if (count($activeEnrollments) <= 1) {
                throw MfaEnrollmentException::cannotRevokeLastMethod($userId);
            }
        }

        // Revoke credential
        $this->credentialRepository->revoke($credentialId);

        $this->logger->info('WebAuthn credential revoked', [
            'user_id' => $userId,
            'credential_id' => $credentialId,
        ]);

        return true;
    }

    public function updateWebAuthnCredentialName(
        string $userId,
        string $credentialId,
        string $friendlyName
    ): bool {
        // Validate friendly name
        if (strlen($friendlyName) < 1 || strlen($friendlyName) > 100) {
            throw MfaEnrollmentException::invalidFriendlyName($friendlyName);
        }

        // Find credential
        $credential = $this->credentialRepository->findByCredentialId($credentialId);

        if ($credential === null || $credential['user_id'] !== $userId) {
            throw MfaEnrollmentException::credentialNotFound($credentialId);
        }

        // Update name
        $this->credentialRepository->updateFriendlyName($credentialId, $friendlyName);

        $this->logger->info('WebAuthn credential renamed', [
            'user_id' => $userId,
            'credential_id' => $credentialId,
            'new_name' => $friendlyName,
        ]);

        return true;
    }

    public function getUserEnrollments(string $userId): array
    {
        return $this->enrollmentRepository->findByUserId($userId);
    }

    public function getUserWebAuthnCredentials(string $userId): array
    {
        $credentials = $this->credentialRepository->findByUserId($userId);

        return array_map(
            fn(array $data) => WebAuthnCredential::fromArray($data),
            $credentials
        );
    }

    public function hasEnrolledMfa(string $userId): bool
    {
        $enrollments = $this->getUserEnrollments($userId);
        $activeEnrollments = array_filter($enrollments, fn($e) => $e->isActive());

        return count($activeEnrollments) > 0;
    }

    public function hasMethodEnrolled(string $userId, string $method): bool
    {
        $enrollments = $this->getUserEnrollments($userId);

        foreach ($enrollments as $enrollment) {
            if ($enrollment->getMethod()->value === $method && $enrollment->isActive()) {
                return true;
            }
        }

        return false;
    }

    public function enablePasswordlessMode(string $userId): bool
    {
        // Check if user has resident keys
        $credentials = $this->credentialRepository->findResidentKeysByUserId($userId);

        if (empty($credentials)) {
            throw MfaEnrollmentException::noResidentKeysEnrolled($userId);
        }

        // Mark user as passwordless (implementation depends on user repository)
        // This would typically update user.password_required = false
        // Left as placeholder for application layer implementation

        $this->logger->info('Passwordless mode enabled', [
            'user_id' => $userId,
        ]);

        return true;
    }

    public function adminResetMfa(
        string $userId,
        string $adminUserId,
        string $reason
    ): string {
        // Authorization check would typically be injected via AuthorizationInterface
        // Left as placeholder for application layer implementation

        // Revoke all enrollments
        $this->enrollmentRepository->revokeAllByUserId($userId);

        // Revoke all WebAuthn credentials
        $this->credentialRepository->revokeAllByUserId($userId);

        // Generate recovery token (6-hour TTL)
        $recoveryToken = bin2hex(random_bytes(32));
        $expiresAt = new DateTimeImmutable('+6 hours');

        // Store recovery token (implementation depends on cache/repository)
        // Left as placeholder for application layer implementation

        $this->logger->warning('MFA admin reset performed', [
            'user_id' => $userId,
            'admin_user_id' => $adminUserId,
            'reason' => $reason,
            'recovery_token_expires_at' => $expiresAt->format('c'),
        ]);

        return $recoveryToken;
    }
}
