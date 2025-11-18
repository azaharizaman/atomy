<?php

declare(strict_types=1);

namespace Nexus\Notifier\Exceptions;

/**
 * Notification Not Found Exception
 *
 * Thrown when a notification cannot be found by ID.
 */
final class NotificationNotFoundException extends NotificationException
{
    public static function forId(string $notificationId): self
    {
        return new self("Notification with ID '{$notificationId}' not found.");
    }
}
