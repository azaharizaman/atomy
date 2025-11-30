<?php

declare(strict_types=1);

namespace Nexus\Notifier\Exceptions;

/**
 * Rate Limit Exceeded Exception
 *
 * Thrown when notification rate limit is exceeded.
 */
final class RateLimitExceededException extends NotificationException
{
    public static function forRecipient(string $recipientId, string $channel): self
    {
        return new self("Rate limit exceeded for recipient '{$recipientId}' on channel '{$channel}'.");
    }

    public static function forChannel(string $channel, int $limit, int $window): self
    {
        return new self(
            "Rate limit exceeded for channel '{$channel}': {$limit} notifications per {$window} seconds."
        );
    }
}
