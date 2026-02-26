<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\Rules;

use Nexus\IdentityOperations\DTOs\MfaStatusResult;

/**
 * Interface for checking MFA enrollment.
 */
interface MfaEnrollmentCheckerInterface
{
    public function isEnrolled(string $userId, string $tenantId): bool;

    public function getEnrollmentStatus(string $userId, string $tenantId): MfaStatusResult;
}
