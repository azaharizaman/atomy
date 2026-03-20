<?php

declare(strict_types=1);

namespace App\Contracts;

interface PasswordResetServiceInterface
{
    public function sendResetLink(string $email): void;

    /**
     * @throws \InvalidArgumentException when token is invalid or expired
     */
    public function resetPassword(string $email, string $token, string $newPassword): void;
}
