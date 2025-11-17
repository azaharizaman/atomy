<?php

declare(strict_types=1);

namespace App\Services;

use Nexus\Identity\Contracts\PasswordHasherInterface;

/**
 * Laravel password hasher implementation
 */
final readonly class LaravelPasswordHasher implements PasswordHasherInterface
{
    public function hash(string $password): string
    {
        return password_hash($password, PASSWORD_ARGON2ID);
    }

    public function verify(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    public function needsRehash(string $hash): bool
    {
        return password_needs_rehash($hash, PASSWORD_ARGON2ID);
    }
}
