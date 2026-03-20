<?php

declare(strict_types=1);

namespace App\Services\Identity;

use Nexus\Identity\Contracts\MfaVerificationServiceInterface;
use Nexus\Identity\Exceptions\MfaVerificationException;
use Nexus\Identity\ValueObjects\UserVerificationRequirement;
use Nexus\Identity\ValueObjects\WebAuthnAuthenticationOptions;

/**
 * MFA verification is not implemented for Atomy-Q.
 */
final class AtomyNoopMfaVerificationService implements MfaVerificationServiceInterface
{
    public function verifyTotp(string $userId, string $code): bool
    {
        return false;
    }

    public function generateWebAuthnAuthenticationOptions(
        ?string $userId = null,
        bool $requireUserVerification = false,
    ): WebAuthnAuthenticationOptions {
        return new WebAuthnAuthenticationOptions(
            challenge: base64_encode(random_bytes(16)),
            timeout: 60000,
            rpId: 'localhost',
            allowCredentials: [],
            userVerification: UserVerificationRequirement::PREFERRED,
        );
    }

    public function verifyWebAuthn(
        string $assertionResponseJson,
        string $expectedChallenge,
        string $expectedOrigin,
        ?string $userId = null,
    ): array {
        throw new \Nexus\Identity\Exceptions\WebAuthnVerificationException('WebAuthn is not enabled for Atomy-Q.');
    }

    public function verifyBackupCode(string $userId, string $code): bool
    {
        return false;
    }

    public function verifyWithFallback(string $userId, array $credentials): array
    {
        throw new MfaVerificationException('MFA verification is not enabled for Atomy-Q');
    }

    public function isRateLimited(string $userId, string $method): bool
    {
        return false;
    }

    public function getRemainingBackupCodesCount(string $userId): int
    {
        return 0;
    }

    public function shouldRegenerateBackupCodes(string $userId): bool
    {
        return false;
    }

    public function recordVerificationAttempt(
        string $userId,
        string $method,
        bool $success,
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): void {
    }

    public function clearRateLimit(string $userId, string $method): bool
    {
        return true;
    }
}
