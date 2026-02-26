<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\Services;

/**
 * Interface for MFA verification.
 */
interface MfaVerifierInterface
{
    public function verify(string $userId, string $code, string $method): bool;

    public function verifyBackupCode(string $userId, string $code): bool;

    public function getFailedAttempts(string $userId): int;
}
