<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\Services;

/**
 * Interface for password changing.
 */
interface PasswordChangerInterface
{
    public function changeWithVerification(string $userId, string $currentPassword, string $newPassword): void;
    public function resetByAdmin(string $userId, string $newPassword): void;
}
