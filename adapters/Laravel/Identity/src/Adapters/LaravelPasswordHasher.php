<?php

declare(strict_types=1);

namespace Nexus\Laravel\Identity\Adapters;

use Illuminate\Support\Facades\Hash;
use Nexus\Identity\Contracts\PasswordHasherInterface;

final readonly class LaravelPasswordHasher implements PasswordHasherInterface
{
    public function hash(string $password): string
    {
        return Hash::make($password);
    }

    public function verify(string $password, string $hash): bool
    {
        return Hash::check($password, $hash);
    }

    public function needsRehash(string $hash): bool
    {
        return Hash::needsRehash($hash);
    }
}
