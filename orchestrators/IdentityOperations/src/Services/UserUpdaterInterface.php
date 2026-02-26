<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\Services;

/**
 * Interface for user updates.
 */
interface UserUpdaterInterface
{
    public function update(
        string $userId,
        ?string $firstName = null,
        ?string $lastName = null,
        ?string $phone = null,
        ?string $locale = null,
        ?string $timezone = null,
        ?array $metadata = null,
    ): void;
}
