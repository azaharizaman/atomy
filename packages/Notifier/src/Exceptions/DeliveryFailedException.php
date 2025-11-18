<?php

declare(strict_types=1);

namespace Nexus\Notifier\Exceptions;

use Throwable;

/**
 * Delivery Failed Exception
 *
 * Thrown when notification delivery fails.
 */
final class DeliveryFailedException extends NotificationException
{
    public static function forChannel(string $channel, string $reason, ?Throwable $previous = null): self
    {
        return new self(
            "Notification delivery failed via '{$channel}' channel: {$reason}",
            0,
            $previous
        );
    }

    public static function providerError(string $provider, string $error, ?Throwable $previous = null): self
    {
        return new self(
            "External provider '{$provider}' returned error: {$error}",
            0,
            $previous
        );
    }
}
