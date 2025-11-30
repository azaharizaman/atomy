<?php

declare(strict_types=1);

namespace Nexus\EventStream\Exceptions;

/**
 * StreamNotFoundException
 *
 * Thrown when attempting to access a stream that does not exist.
 *
 * @package Nexus\EventStream\Exceptions
 */
class StreamNotFoundException extends EventStreamException
{
    public function __construct(
        public readonly string $aggregateId,
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        if (empty($message)) {
            $message = sprintf('Event stream not found for aggregate "%s"', $aggregateId);
        }

        parent::__construct($message, $code, $previous);
    }
}
