<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\Rules;

/**
 * Interface for checking user status.
 */
interface UserStatusCheckerInterface
{
    public function getStatus(string $userId): ?UserStatus;

    public function isActive(string $userId): bool;

    public function isSuspended(string $userId): bool;

    public function isDeactivated(string $userId): bool;
}
