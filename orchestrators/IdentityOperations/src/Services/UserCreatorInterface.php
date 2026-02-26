<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\Services;

/**
 * Interface for user creation.
 */
interface UserCreatorInterface
{
    public function create(
        string $email,
        string $password,
        string $firstName,
        string $lastName,
        ?string $phone = null,
        ?string $locale = null,
        ?string $timezone = null,
        ?array $metadata = null,
    ): string;
}
