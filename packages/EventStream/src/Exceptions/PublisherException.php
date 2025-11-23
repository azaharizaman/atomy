<?php

declare(strict_types=1);

namespace Nexus\EventStream\Exceptions;

/**
 * Publisher Exception
 *
 * Thrown when an event publisher fails to dispatch an event to the event bus.
 * This exception triggers transaction rollback to maintain consistency between
 * the event store and read models/notification systems.
 *
 * Common causes:
 * - Queue connection unavailable (Redis/RabbitMQ down)
 * - Event dispatcher misconfigured
 * - Maximum queue depth exceeded
 * - Network partition between application and message broker
 *
 * Requirements satisfied:
 * - REL-EVS-7411: Publisher failure triggers transaction rollback
 * - FUN-EVS-7230: Event publishing transactional consistency
 *
 * @package Nexus\EventStream\Exceptions
 */
class PublisherException extends EventStreamException
{
    public function __construct(
        string $message = 'Failed to publish event',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Create exception for queue connection failure
     *
     * @param string $queueDriver Queue driver name (e.g., 'redis', 'rabbitmq')
     * @param \Throwable|null $previous Previous exception
     * @return self
     */
    public static function queueUnavailable(string $queueDriver, ?\Throwable $previous = null): self
    {
        return new self(
            sprintf('Queue driver "%s" is unavailable. Event cannot be published.', $queueDriver),
            0,
            $previous
        );
    }

    /**
     * Create exception for event dispatcher failure
     *
     * @param string $eventType Event type that failed to dispatch
     * @param \Throwable|null $previous Previous exception
     * @return self
     */
    public static function dispatchFailed(string $eventType, ?\Throwable $previous = null): self
    {
        return new self(
            sprintf('Failed to dispatch event of type "%s" to event bus.', $eventType),
            0,
            $previous
        );
    }
}
