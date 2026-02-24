<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\Services;

use Nexus\IdentityOperations\Contracts\MfaServiceInterface;
use Nexus\IdentityOperations\DTOs\MfaEnableRequest;
use Nexus\IdentityOperations\DTOs\MfaEnableResult;
use Nexus\IdentityOperations\DTOs\MfaVerifyRequest;
use Nexus\IdentityOperations\DTOs\MfaVerifyResult;
use Nexus\IdentityOperations\DTOs\MfaDisableRequest;
use Nexus\IdentityOperations\DTOs\MfaDisableResult;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Service for MFA operations.
 */
final readonly class MfaService implements MfaServiceInterface
{
    private const MAX_VERIFICATION_ATTEMPTS = 5;

    public function __construct(
        private MfaEnrollerInterface $enroller,
        private MfaVerifierInterface $verifier,
        private MfaDisablerInterface $disabler,
        private BackupCodeGeneratorInterface $backupCodeGenerator,
        private AuditLoggerInterface $auditLogger,
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    public function enable(MfaEnableRequest $request): MfaEnableResult
    {
        $this->logger->info('Enabling MFA', [
            'user_id' => $request->userId,
            'method' => $request->method->value,
        ]);

        try {
            $enrollment = $this->enroller->enroll(
                userId: $request->userId,
                method: $request->method->value,
                phone: $request->phone,
                email: $request->email,
            );

            $this->auditLogger->log(
                'mfa.enabled',
                $request->userId,
                ['method' => $request->method->value]
            );

            // Generate backup codes
            $backupCodes = $this->generateBackupCodes($request->userId);

            return MfaEnableResult::success(
                userId: $request->userId,
                secret: $enrollment['secret'] ?? null,
                qrCodeUrl: $enrollment['qr_code_url'] ?? null,
                backupCodes: $backupCodes,
            );

        } catch (\Throwable $e) {
            $this->logger->error('Failed to enable MFA', [
                'user_id' => $request->userId,
                'error' => $e->getMessage(),
            ]);

            return MfaEnableResult::failure(
                message: 'Failed to enable MFA: ' . $e->getMessage()
            );
        }
    }

    public function verify(MfaVerifyRequest $request): MfaVerifyResult
    {
        $this->logger->info('Verifying MFA', [
            'user_id' => $request->userId,
            'method' => $request->method->value,
        ]);

        try {
            $isValid = $this->verifier->verify(
                userId: $request->userId,
                code: $request->code,
                method: $request->method->value,
            );

            if ($isValid) {
                $this->auditLogger->log(
                    'mfa.verified',
                    $request->userId,
                    ['method' => $request->method->value]
                );

                return MfaVerifyResult::success(
                    userId: $request->userId,
                );
            }

            // Check remaining attempts
            $attempts = $this->verifier->getFailedAttempts($request->userId);
            $remaining = max(0, self::MAX_VERIFICATION_ATTEMPTS - $attempts);

            $this->auditLogger->log(
                'mfa.verification_failed',
                $request->userId,
                ['method' => $request->method->value, 'attempts' => $attempts]
            );

            return MfaVerifyResult::failure(
                message: 'Invalid MFA code',
                remainingAttempts: $remaining,
            );

        } catch (\Throwable $e) {
            $this->logger->error('Failed to verify MFA', [
                'user_id' => $request->userId,
                'error' => $e->getMessage(),
            ]);

            return MfaVerifyResult::failure(
                message: 'Failed to verify MFA: ' . $e->getMessage()
            );
        }
    }

    public function disable(MfaDisableRequest $request): MfaDisableResult
    {
        $this->logger->info('Disabling MFA', [
            'user_id' => $request->userId,
        ]);

        try {
            $this->disabler->disable($request->userId);

            $this->auditLogger->log(
                'mfa.disabled',
                $request->userId,
                ['disabled_by' => $request->disabledBy, 'reason' => $request->reason]
            );

            return MfaDisableResult::success(
                userId: $request->userId,
            );

        } catch (\Throwable $e) {
            $this->logger->error('Failed to disable MFA', [
                'user_id' => $request->userId,
                'error' => $e->getMessage(),
            ]);

            return MfaDisableResult::failure(
                message: 'Failed to disable MFA: ' . $e->getMessage()
            );
        }
    }

    public function getStatus(string $userId): array
    {
        return $this->enroller->getStatus($userId);
    }

    public function generateBackupCodes(string $userId): array
    {
        return $this->backupCodeGenerator->generate($userId);
    }

    public function validateBackupCode(string $userId, string $code): bool
    {
        return $this->verifier->verifyBackupCode($userId, $code);
    }
}

/**
 * Interface for MFA enrollment.
 */
interface MfaEnrollerInterface
{
    /**
     * @return array{secret: string, qr_code_url: string}
     */
    public function enroll(string $userId, string $method, ?string $phone = null, ?string $email = null): array;

    public function getStatus(string $userId): array;
}

/**
 * Interface for MFA verification.
 */
interface MfaVerifierInterface
{
    public function verify(string $userId, string $code, string $method): bool;

    public function verifyBackupCode(string $userId, string $code): bool;

    public function getFailedAttempts(string $userId): int;
}

/**
 * Interface for MFA disabling.
 */
interface MfaDisablerInterface
{
    public function disable(string $userId): void;
}

/**
 * Interface for backup code generation.
 */
interface BackupCodeGeneratorInterface
{
    public function generate(string $userId): array;
}
