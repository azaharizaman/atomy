<?php

declare(strict_types=1);

namespace Nexus\Domain\Identity\Services;

use DateTimeImmutable;
use Nexus\Domain\Identity\Contracts\CacheRepositoryInterface;
use Nexus\Domain\Identity\Contracts\MfaEnrollmentRepositoryInterface;
use Nexus\Domain\Identity\Contracts\MfaVerificationServiceInterface;
use Nexus\Domain\Identity\Contracts\TotpManagerInterface;
use Nexus\Domain\Identity\Contracts\WebAuthnCredentialRepositoryInterface;
use Nexus\Domain\Identity\Contracts\WebAuthnManagerInterface;
use Nexus\Domain\Identity\ValueObjects\MfaMethod;
use Nexus\Domain\Identity\Exceptions\MfaVerificationException;
use Nexus\Domain\Identity\ValueObjects\BackupCode;
use Nexus\Domain\Identity\ValueObjects\TotpSecret;
use Nexus\Domain\Identity\ValueObjects\WebAuthnAuthenticationOptions;
use Nexus\Domain\Identity\ValueObjects\WebAuthnCredential;
use Psr\Log\LoggerInterface;

/**
 * MFA Verification Service
 *
 * Orchestrates multi-factor authentication verification with rate limiting,
 * fallback chains, and security policies for TOTP, WebAuthn, and backup codes.
 *
 * @package Nexus\Domain\Identity
 */
final readonly class MfaVerificationService implements MfaVerificationServiceInterface
{
    private const RATE_LIMIT_WINDOW = 900; // 15 minutes in seconds
    private const RATE_LIMIT_MAX_ATTEMPTS = 5;
    private const BACKUP_CODE_REGENERATION_THRESHOLD = 2;

    public function __construct(
        private MfaEnrollmentRepositoryInterface $enrollmentRepository,
        private WebAuthnCredentialRepositoryInterface $credentialRepository,
        private TotpManagerInterface $totpManager,
        private WebAuthnManagerInterface $webAuthnManager,
        private CacheRepositoryInterface $cache,
        private LoggerInterface $logger
    ) {}

    public function verifyTotp(string $userId, string $code): bool
    {
        // Check rate limit
        if ($this->isRateLimited($userId, MfaMethod::TOTP->value)) {
            $retryAfter = $this->getRateLimitRetryAfter($userId, MfaMethod::TOTP->value);
            throw MfaVerificationException::rateLimited($userId, MfaMethod::TOTP->value, $retryAfter);
        }

        // Find TOTP enrollment
        $enrollment = $this->enrollmentRepository->findActiveByUserAndMethod(
            $userId,
            MfaMethod::TOTP
        );

        if ($enrollment === null) {
            throw MfaVerificationException::methodNotEnrolled($userId, MfaMethod::TOTP->value);
        }

        // Reconstruct TotpSecret from stored data
        $secret = TotpSecret::fromArray($enrollment->getSecret());

        // Verify code
        $isValid = $this->totpManager->verifyCode($secret, $code);

        // Record attempt
        $this->recordVerificationAttempt(
            userId: $userId,
            method: MfaMethod::TOTP->value,
            success: $isValid
        );

        if (!$isValid) {
            $this->logger->warning('TOTP verification failed', [
                'user_id' => $userId,
            ]);

            throw MfaVerificationException::invalidTotpCode($userId);
        }

        // Update last used timestamp
        $this->enrollmentRepository->updateLastUsed($enrollment->getId(), new DateTimeImmutable());

        $this->logger->info('TOTP verified successfully', [
            'user_id' => $userId,
        ]);

        return true;
    }

    public function generateWebAuthnAuthenticationOptions(
        ?string $userId = null,
        bool $requireUserVerification = false
    ): WebAuthnAuthenticationOptions {
        $allowCredentialIds = [];

        // If user-specific (not usernameless), get their credentials
        if ($userId !== null) {
            $credentials = $this->credentialRepository->findByUserId($userId);
            $allowCredentialIds = array_map(
                fn(array $cred) => $cred['credential_id'],
                $credentials
            );

            if (empty($allowCredentialIds)) {
                throw MfaVerificationException::methodNotEnrolled($userId, MfaMethod::PASSKEY->value);
            }
        }

        // Generate authentication options
        $options = $this->webAuthnManager->generateAuthenticationOptions(
            allowCredentialIds: $allowCredentialIds,
            requireUserVerification: $requireUserVerification
        );

        $this->logger->info('WebAuthn authentication options generated', [
            'user_id' => $userId,
            'usernameless' => $userId === null,
            'require_user_verification' => $requireUserVerification,
        ]);

        return $options;
    }

    public function verifyWebAuthn(
        string $assertionResponseJson,
        string $expectedChallenge,
        string $expectedOrigin,
        ?string $userId = null
    ): array {
        // For user-specific flow, check rate limit
        if ($userId !== null && $this->isRateLimited($userId, MfaMethod::PASSKEY->value)) {
            $retryAfter = $this->getRateLimitRetryAfter($userId, MfaMethod::PASSKEY->value);
            throw MfaVerificationException::rateLimited($userId, MfaMethod::PASSKEY->value, $retryAfter);
        }

        // Decode assertion to extract credential ID
        $assertion = json_decode($assertionResponseJson, true);
        if (!isset($assertion['id'])) {
            throw MfaVerificationException::verificationFailed('Invalid assertion format');
        }

        $credentialId = $assertion['id'];

        // Find stored credential
        $storedCredential = $this->credentialRepository->findByCredentialId($credentialId);

        if ($storedCredential === null) {
            throw MfaVerificationException::verificationFailed('Credential not found');
        }

        // Reconstruct WebAuthnCredential value object
        $credential = WebAuthnCredential::fromArray($storedCredential);

        // Verify assertion
        $verificationResult = $this->webAuthnManager->verifyAuthentication(
            assertionJson: $assertionResponseJson,
            expectedChallenge: $expectedChallenge,
            expectedOrigin: $expectedOrigin,
            storedCredential: $credential
        );

        $verifiedUserId = $storedCredential['user_id'];
        $newSignCount = $verificationResult['newSignCount'];

        // If user-specific flow, verify user ID matches
        if ($userId !== null && $verifiedUserId !== $userId) {
            throw MfaVerificationException::verificationFailed('User ID mismatch');
        }

        // Record successful attempt
        $this->recordVerificationAttempt(
            userId: $verifiedUserId,
            method: MfaMethod::PASSKEY->value,
            success: true
        );

        // Update credential sign count and last used
        $this->credentialRepository->updateAfterAuthentication(
            credentialId: $credentialId,
            newSignCount: $newSignCount,
            lastUsedAt: new DateTimeImmutable()
        );

        $this->logger->info('WebAuthn verified successfully', [
            'user_id' => $verifiedUserId,
            'credential_id' => $credentialId,
            'new_sign_count' => $newSignCount,
        ]);

        return [
            'userId' => $verifiedUserId,
            'credentialId' => $credentialId,
        ];
    }

    public function verifyBackupCode(string $userId, string $code): bool
    {
        // Check rate limit
        if ($this->isRateLimited($userId, MfaMethod::BACKUP_CODES->value)) {
            $retryAfter = $this->getRateLimitRetryAfter($userId, MfaMethod::BACKUP_CODES->value);
            throw MfaVerificationException::rateLimited(
                $userId,
                MfaMethod::BACKUP_CODES->value,
                $retryAfter
            );
        }

        // Find all backup code enrollments
        $enrollments = $this->enrollmentRepository->findActiveBackupCodes($userId);

        if (empty($enrollments)) {
            throw MfaVerificationException::methodNotEnrolled($userId, MfaMethod::BACKUP_CODES->value);
        }

        // Try to verify against each unconsumed code
        foreach ($enrollments as $enrollment) {
            $secret = $enrollment->getSecret();

            // Skip consumed codes
            if ($secret['consumed_at'] !== null) {
                continue;
            }

            // Use constant-time comparison to prevent timing attacks
            if (hash_equals($secret['hash'], BackupCode::hash($code))) {
                // Mark code as consumed
                $this->enrollmentRepository->consumeBackupCode(
                    $enrollment->getId(),
                    new DateTimeImmutable()
                );

                // Record successful attempt
                $this->recordVerificationAttempt(
                    userId: $userId,
                    method: MfaMethod::BACKUP_CODES->value,
                    success: true
                );

                $this->logger->info('Backup code verified successfully', [
                    'user_id' => $userId,
                    'enrollment_id' => $enrollment->getId(),
                ]);

                return true;
            }
        }

        // Record failed attempt
        $this->recordVerificationAttempt(
            userId: $userId,
            method: MfaMethod::BACKUP_CODES->value,
            success: false
        );

        $this->logger->warning('Backup code verification failed', [
            'user_id' => $userId,
        ]);

        throw MfaVerificationException::invalidBackupCode($userId);
    }

    public function verifyWithFallback(string $userId, array $credentials): array
    {
        $lastException = null;

        // Try each method in order
        foreach ($credentials as $method => $code) {
            try {
                $verified = match ($method) {
                    'totp' => $this->verifyTotp($userId, $code),
                    'backup_code' => $this->verifyBackupCode($userId, $code),
                    default => false,
                };

                if ($verified) {
                    return [
                        'method' => $method,
                        'verified' => true,
                    ];
                }
            } catch (MfaVerificationException $e) {
                $lastException = $e;
                // Continue to next method
                continue;
            }
        }

        // All methods failed
        if ($lastException !== null) {
            throw $lastException;
        }

        throw MfaVerificationException::allMethodsFailed($userId);
    }

    public function isRateLimited(string $userId, string $method): bool
    {
        $key = $this->getRateLimitKey($userId, $method);
        $attempts = $this->cache->get($key, 0);

        return $attempts >= self::RATE_LIMIT_MAX_ATTEMPTS;
    }

    public function getRemainingBackupCodesCount(string $userId): int
    {
        $enrollments = $this->enrollmentRepository->findActiveBackupCodes($userId);

        $unconsumed = array_filter(
            $enrollments,
            fn($e) => $e['secret']['consumed_at'] === null
        );

        return count($unconsumed);
    }

    public function shouldRegenerateBackupCodes(string $userId): bool
    {
        $remaining = $this->getRemainingBackupCodesCount($userId);

        return $remaining <= self::BACKUP_CODE_REGENERATION_THRESHOLD;
    }

    public function recordVerificationAttempt(
        string $userId,
        string $method,
        bool $success,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): void {
        // Record in cache for rate limiting
        if (!$success) {
            $key = $this->getRateLimitKey($userId, $method);
            $attempts = $this->cache->get($key, 0);
            $this->cache->put($key, $attempts + 1, self::RATE_LIMIT_WINDOW);
        } else {
            // Clear rate limit on success
            $this->clearRateLimit($userId, $method);
        }

        // Log attempt for audit trail
        $this->logger->info('MFA verification attempt recorded', [
            'user_id' => $userId,
            'method' => $method,
            'success' => $success,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);
    }

    public function clearRateLimit(string $userId, string $method): bool
    {
        $key = $this->getRateLimitKey($userId, $method);
        $this->cache->forget($key);

        $this->logger->info('Rate limit cleared', [
            'user_id' => $userId,
            'method' => $method,
        ]);

        return true;
    }

    /**
     * Get rate limit cache key
     *
     * @param string $userId User identifier
     * @param string $method MFA method
     * @return string Cache key
     */
    private function getRateLimitKey(string $userId, string $method): string
    {
        return "mfa:rate_limit:{$userId}:{$method}";
    }

    /**
     * Get seconds until rate limit expires
     *
     * @param string $userId User identifier
     * @param string $method MFA method
     * @return int Seconds until retry allowed
     */
    private function getRateLimitRetryAfter(string $userId, string $method): int
    {
        $key = $this->getRateLimitKey($userId, $method);
        $ttl = $this->cache->getTtl($key);

        return max(0, $ttl);
    }
}
