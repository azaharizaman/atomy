<?php

declare(strict_types=1);

namespace App\Service\Identity\Adapters;

use Nexus\IdentityOperations\DTOs\MfaEnableResult;
use Nexus\IdentityOperations\DTOs\MfaStatusResult;
use Nexus\IdentityOperations\Services\MfaEnrollerInterface;
use Nexus\IdentityOperations\Services\MfaVerifierInterface;
use Nexus\IdentityOperations\Services\MfaDisablerInterface;
use Nexus\IdentityOperations\Services\BackupCodeGeneratorInterface;
use Nexus\IdentityOperations\DTOs\MfaMethod;

final readonly class MfaAdapter implements MfaEnrollerInterface, MfaVerifierInterface, MfaDisablerInterface, BackupCodeGeneratorInterface
{
    public function enroll(string $userId, string $tenantId, MfaMethod $method, ?string $phone = null, ?string $email = null): MfaEnableResult
    {
        return MfaEnableResult::success($userId, 'mfa-secret', ['backup-code-1', 'backup-code-2']);
    }

    public function getStatus(string $userId, string $tenantId): MfaStatusResult
    {
        return new MfaStatusResult(
            userId: $userId,
            isEnabled: false,
            methods: [],
            isEnrolled: false
        );
    }

    public function verify(string $userId, MfaMethod $method, string $code): bool
    {
        return $code === '123456'; // Simple mock verification
    }

    public function verifyBackupCode(string $userId, string $code, ?string $tenantId = null): bool
    {
        return $code === 'backup-code';
    }

    public function getFailedAttempts(string $userId, ?string $tenantId = null): int
    {
        return 0;
    }

    public function disable(string $userId): void
    {
        // No-op
    }

    public function generate(string $userId): array
    {
        return array_fill(0, 10, 'backup-code');
    }
}
