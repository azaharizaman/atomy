<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\Rules;

/**
 * Interface for checking MFA enrollment.
 */
interface MfaEnrollmentCheckerInterface
{
    public function isEnrolled(string $userId, ?string $tenantId = null): bool;

    public function getEnrollmentStatus(string $userId, ?string $tenantId = null): array;
}
