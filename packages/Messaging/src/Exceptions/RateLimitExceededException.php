<?php

declare(strict_types=1);

namespace Nexus\Messaging\Exceptions;

/**
 * Exception thrown when rate limit is exceeded
 * 
 * @package Nexus\Messaging
 */
final class RateLimitExceededException extends MessagingException
{
    public static function forTenant(string $tenantId, int $limit): self
    {
        return new self("Rate limit of {$limit} messages exceeded for tenant '{$tenantId}'");
    }

    public static function forChannel(string $channel, int $limit): self
    {
        return new self("Rate limit of {$limit} messages exceeded for channel '{$channel}'");
    }
}
