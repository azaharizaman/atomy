<?php

declare(strict_types=1);

namespace App\Service\Identity\Adapters;

use Nexus\IdentityOperations\Services\NotificationSenderInterface;

final readonly class NotificationSenderAdapter implements NotificationSenderInterface
{
    public function sendWelcome(string $userId, ?string $temporaryPassword = null): void
    {
        // No-op for now. In a real app, this would send an email or push notification.
    }

    public function sendMfaCode(string $userId, string $code, string $method): void
    {
        // No-op for now.
    }

    public function sendPasswordReset(string $userId, string $token): void
    {
        // No-op for now.
    }
}
