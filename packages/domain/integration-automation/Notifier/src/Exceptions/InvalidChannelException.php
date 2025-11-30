<?php

declare(strict_types=1);

namespace Nexus\Notifier\Exceptions;

/**
 * Invalid Channel Exception
 *
 * Thrown when an invalid or unsupported channel is specified.
 */
final class InvalidChannelException extends NotificationException
{
    public static function forChannel(string $channel): self
    {
        return new self("Invalid or unsupported notification channel: '{$channel}'.");
    }

    public static function notAvailable(string $channel): self
    {
        return new self("Notification channel '{$channel}' is not currently available.");
    }
}
