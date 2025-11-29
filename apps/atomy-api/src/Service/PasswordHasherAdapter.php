<?php

declare(strict_types=1);

namespace App\Service;

use Nexus\Identity\Contracts\PasswordHasherInterface;

/**
 * Application adapter for Nexus\Identity PasswordHasherInterface.
 *
 * This implementation uses PHP's password_* functions. It prefers Argon2id
 * when available and falls back to bcrypt. It supports an optional application
 * pepper (app secret) which is prepended to the password before hashing.
 */
final readonly class PasswordHasherAdapter implements PasswordHasherInterface
{
    private const DEFAULT_ARGON2_OPTIONS = [
        'memory_cost' => 1 << 17, // 128 MB
        'time_cost' => 4,
        'threads' => 2,
    ];

    private const BCRYPT_COST = 12;

    public function __construct(private ?string $pepper = null)
    {
    }

    public function hash(string $password): string
    {
        $plain = $this->applyPepper($password);

        if (defined('PASSWORD_ARGON2ID')) {
            return password_hash($plain, PASSWORD_ARGON2ID, self::DEFAULT_ARGON2_OPTIONS);
        }

        return password_hash($plain, PASSWORD_BCRYPT, ['cost' => self::BCRYPT_COST]);
    }

    public function verify(string $password, string $hash): bool
    {
        $plain = $this->applyPepper($password);

        return password_verify($plain, $hash);
    }

    public function needsRehash(string $hash): bool
    {
        if (defined('PASSWORD_ARGON2ID')) {
            return password_needs_rehash($hash, PASSWORD_ARGON2ID, self::DEFAULT_ARGON2_OPTIONS);
        }

        return password_needs_rehash($hash, PASSWORD_BCRYPT, ['cost' => self::BCRYPT_COST]);
    }

    private function applyPepper(string $password): string
    {
        if ($this->pepper === null || $this->pepper === '') {
            return $password;
        }

        // Prepend pepper — application secret isn't stored alongside hashes
        return $this->pepper . $password;
    }
}
