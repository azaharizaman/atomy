<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\Services;

/**
 * Interface for user state management.
 */
interface UserStateManagerInterface
{
    public function suspend(string $userId): void;
    public function activate(string $userId): void;
    public function deactivate(string $userId): void;
    public function setAccessEnabled(string $userId, bool $enabled): void;
}
