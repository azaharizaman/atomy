<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\Services;

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
