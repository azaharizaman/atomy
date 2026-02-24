<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\Contracts;

use Nexus\IdentityOperations\DTOs\UserContext;

/**
 * Interface for user context data provider.
 */
interface UserContextProviderInterface
{
    /**
     * Get user context by user ID.
     */
    public function getContext(string $userId): UserContext;

    /**
     * Check if user exists.
     */
    public function userExists(string $userId): bool;

    /**
     * Check if user is active.
     */
    public function isUserActive(string $userId): bool;
}
