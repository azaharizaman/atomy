<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\Services;

use Nexus\IdentityOperations\DTOs\MfaMethod;

/**
 * Interface for MFA verification.
 */
interface MfaVerifierInterface
{
    public function verify(string $userId, MfaMethod $method, string $code): bool;

    public function verifyBackupCode(string $userId, string $code, ?string $tenantId = null): bool;

    public function getFailedAttempts(string $userId, ?string $tenantId = null): int;
}
