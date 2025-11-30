<?php

declare(strict_types=1);

namespace Nexus\EventStream\Contracts;

/**
 * ProjectorInterface
 *
 * Contract for building read models (projections) from event streams.
 * Projectors apply events to rebuild aggregate state or create specialized views.
 *
 * Requirements satisfied:
 * - FUN-EVS-7203: Define ProjectorInterface for rebuilding state from event streams
 * - FUN-EVS-7215: Provide projection engine for building read models
 * - FUN-EVS-7216: Support multiple projections per stream
 *
 * @package Nexus\EventStream\Contracts
 */
interface ProjectorInterface
{
    /**
     * Get the unique name of this projector
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Get the event types this projector handles
     *
     * @return string[] Array of event type class names
     */
    public function getHandledEventTypes(): array;

    /**
     * Project a single event (apply it to the read model)
     *
     * @param EventInterface $event The event to project
     * @return void
     */
    public function project(EventInterface $event): void;

    /**
     * Reset the projection (clear all data)
     *
     * @return void
     */
    public function reset(): void;

    /**
     * Get the last processed event ID (for resumption)
     *
     * @return string|null
     */
    public function getLastProcessedEventId(): ?string;

    /**
     * Set the last processed event ID (for resumption)
     *
     * @param string $eventId
     * @return void
     */
    public function setLastProcessedEventId(string $eventId): void;
}
