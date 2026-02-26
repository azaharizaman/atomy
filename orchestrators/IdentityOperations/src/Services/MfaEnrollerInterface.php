<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\Services;

use Nexus\IdentityOperations\DTOs\MfaMethod;
use Nexus\IdentityOperations\DTOs\MfaEnableResult;

/**
 * Interface for MFA enrollment.
 */
interface MfaEnrollerInterface
{
    public function enroll(string $userId, MfaMethod $method, ?string $phone = null, ?string $email = null): MfaEnableResult;

    public function getStatus(string $userId): array;
}
