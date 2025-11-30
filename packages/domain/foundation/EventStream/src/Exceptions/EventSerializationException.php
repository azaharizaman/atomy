<?php

declare(strict_types=1);

namespace Nexus\EventStream\Exceptions;

/**
 * EventSerializationException
 *
 * Thrown when event serialization or deserialization fails.
 *
 * @package Nexus\EventStream\Exceptions
 */
class EventSerializationException extends EventStreamException
{
    public function __construct(
        public readonly string $eventType,
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        if (empty($message)) {
            $message = sprintf('Failed to serialize/deserialize event of type "%s"', $eventType);
        } else {
            // Prepend event type to custom message for context
            $message = sprintf('[%s] %s', $eventType, $message);
        }

        parent::__construct($message, $code, $previous);
    }
}
