<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\Contracts;

use Nexus\IdentityOperations\DTOs\MfaEnableRequest;
use Nexus\IdentityOperations\DTOs\MfaEnableResult;
use Nexus\IdentityOperations\DTOs\MfaVerifyRequest;
use Nexus\IdentityOperations\DTOs\MfaVerifyResult;
use Nexus\IdentityOperations\DTOs\MfaDisableRequest;
use Nexus\IdentityOperations\DTOs\MfaDisableResult;

/**
 * Coordinator interface for MFA management.
 */
interface MfaCoordinatorInterface extends CoordinatorInterface
{
    /**
     * Enable MFA for a user.
     */
    public function enable(MfaEnableRequest $request): MfaEnableResult;

    /**
     * Verify MFA code.
     */
    public function verify(MfaVerifyRequest $request): MfaVerifyResult;

    /**
     * Disable MFA for a user.
     */
    public function disable(MfaDisableRequest $request): MfaDisableResult;

    /**
     * Get MFA status for a user.
     */
    public function getStatus(string $userId): array;

    /**
     * Generate backup codes for a user.
     */
    public function generateBackupCodes(string $userId): array;

    /**
     * Validate backup code.
     */
    public function validateBackupCode(string $userId, string $code): bool;
}
