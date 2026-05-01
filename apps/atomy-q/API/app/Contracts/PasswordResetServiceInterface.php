<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * Boundary for tenant-aware password reset initiation and consumption.
 *
 * Implementations should avoid account enumeration on reset-link requests and
 * consume valid reset tokens atomically when changing credentials.
 */
interface PasswordResetServiceInterface
{
    public function sendResetLink(string $email): void;

    /**
     * @throws \InvalidArgumentException when token is invalid or expired
     */
    public function resetPassword(string $email, string $token, string $newPassword): void;
}
