<?php

declare(strict_types=1);

namespace Nexus\Messaging\Exceptions;

/**
 * Exception thrown when message delivery fails
 * 
 * @package Nexus\Messaging
 */
final class MessageDeliveryException extends MessagingException
{
    public static function forMessage(string $messageId, string $reason): self
    {
        return new self("Failed to deliver message '{$messageId}': {$reason}");
    }

    public static function providerError(string $provider, string $error): self
    {
        return new self("Provider '{$provider}' returned error: {$error}");
    }
}
