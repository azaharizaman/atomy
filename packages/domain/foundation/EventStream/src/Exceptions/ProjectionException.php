<?php

declare(strict_types=1);

namespace Nexus\EventStream\Exceptions;

/**
 * ProjectionException
 *
 * Thrown when a projection fails to process an event.
 *
 * Requirements satisfied:
 * - BUS-EVS-7109: Failed projections MUST be retryable without corrupting event stream
 * - REL-EVS-7404: Projection failures logged without corrupting event stream
 *
 * @package Nexus\EventStream\Exceptions
 */
class ProjectionException extends EventStreamException
{
    public function __construct(
        public readonly string $projectorName,
        public readonly string $eventId,
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        if (empty($message)) {
            $message = sprintf(
                'Projection "%s" failed to process event "%s"',
                $projectorName,
                $eventId
            );
        }

        parent::__construct($message, $code, $previous);
    }
}
