<?php

declare(strict_types=1);

namespace Nexus\EventStream\Contracts;

/**
 * StreamInterface
 *
 * Represents an event stream for a specific aggregate.
 *
 * Requirements satisfied:
 * - ARC-EVS-7002: All data structures defined via interfaces
 *
 * @package Nexus\EventStream\Contracts
 */
interface StreamInterface
{
    /**
     * Get the aggregate ID for this stream
     *
     * @return string
     */
    public function getAggregateId(): string;

    /**
     * Get the current version of the stream
     *
     * @return int
     */
    public function getCurrentVersion(): int;

    /**
     * Get all events in the stream
     *
     * @return EventInterface[]
     */
    public function getEvents(): array;

    /**
     * Get the total number of events in the stream
     *
     * @return int
     */
    public function getEventCount(): int;

    /**
     * Check if the stream is empty
     *
     * @return bool
     */
    public function isEmpty(): bool;
}
