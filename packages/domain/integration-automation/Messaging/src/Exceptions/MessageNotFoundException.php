<?php

declare(strict_types=1);

namespace Nexus\Messaging\Exceptions;

/**
 * Exception thrown when message record is not found
 * 
 * @package Nexus\Messaging
 */
final class MessageNotFoundException extends MessagingException
{
    public static function withId(string $id): self
    {
        return new self("Message with ID '{$id}' not found");
    }
}
