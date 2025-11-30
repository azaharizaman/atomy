<?php

declare(strict_types=1);

namespace Nexus\Messaging\Exceptions;

/**
 * Exception thrown when invalid channel is used
 * 
 * @package Nexus\Messaging
 */
final class InvalidChannelException extends MessagingException
{
    public static function unsupported(string $channel): self
    {
        return new self("Channel '{$channel}' is not supported");
    }

    public static function notConfigured(string $channel): self
    {
        return new self("Channel '{$channel}' is not configured");
    }
}
