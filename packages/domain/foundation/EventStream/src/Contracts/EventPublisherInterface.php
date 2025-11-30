<?php

declare(strict_types=1);

namespace Nexus\EventStream\Contracts;

use Nexus\EventStream\Exceptions\PublisherException;

/**
 * Event Publisher Interface
 *
 * Contract for publishing saved domain events to the application's event bus.
 * Publishers are called after events are successfully persisted to the EventStore,
 * enabling asynchronous processing, notifications, and read model updates.
 *
 * CRITICAL: Publishers MUST throw PublisherException on failure to maintain
 * transactional consistency. A failed publish triggers transaction rollback,
 * preventing inconsistent state where events are stored but never processed.
 *
 * Requirements satisfied:
 * - FUN-EVS-7230: Event publishing with transactional consistency
 * - REL-EVS-7411: Publisher failure triggers transaction rollback
 * - INT-EVS-7601: Integration with application event bus
 *
 * @package Nexus\EventStream\Contracts
 */
interface EventPublisherInterface
{
    /**
     * Publish a single domain event to the application's event bus.
     *
     * This method is typically called AFTER the event has been successfully
     * committed to the EventStore within the same database transaction.
     *
     * Implementation modes:
     * - Synchronous: Dispatch event handlers immediately (default)
     * - Asynchronous: Queue event for background processing
     *
     * @param EventInterface $event The domain event to publish
     * @param string $streamId The stream identifier for context/routing
     * @return void
     * @throws PublisherException If publishing fails (triggers transaction rollback)
     */
    public function publish(EventInterface $event, string $streamId): void;
}
