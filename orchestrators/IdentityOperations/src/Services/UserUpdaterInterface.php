<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\Services;

use Nexus\IdentityOperations\DTOs\UserUpdateRequest;

/**
 * Interface for user updates.
 */
interface UserUpdaterInterface
{
    public function update(string $userId, UserUpdateRequest $request): void;
}
