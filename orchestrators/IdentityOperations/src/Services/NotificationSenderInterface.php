<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\Services;

/**
 * Interface for notifications.
 */
interface NotificationSenderInterface
{
    public function sendWelcome(string $userId, ?string $temporaryPassword = null): void;
}
