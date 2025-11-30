<?php

declare(strict_types=1);

namespace Nexus\EventStream\Contracts;

use DateTimeImmutable;

/**
 * StreamReaderInterface
 *
 * Contract for reading event streams with various filtering options.
 * Provides query capabilities for event streams.
 *
 * Requirements satisfied:
 * - FUN-EVS-7202: Define StreamReaderInterface for reading event streams with filtering
 * - FUN-EVS-7206: Read event stream by aggregate ID with optional version range filtering
 * - FUN-EVS-7207: Read event stream by event type
 *
 * @package Nexus\EventStream\Contracts
 */
interface StreamReaderInterface
{
    /**
     * Read all events for an aggregate
     *
     * @param string $aggregateId The aggregate identifier
     * @return EventInterface[]
     */
    public function readStream(string $aggregateId): array;

    /**
     * Read events for an aggregate within a version range
     *
     * @param string $aggregateId The aggregate identifier
     * @param int $fromVersion Starting version (inclusive)
     * @param int|null $toVersion Ending version (inclusive, null = latest)
     * @return EventInterface[]
     */
    public function readStreamFromVersion(
        string $aggregateId,
        int $fromVersion,
        ?int $toVersion = null
    ): array;

    /**
     * Read events for an aggregate up to a specific timestamp (temporal query)
     *
     * @param string $aggregateId The aggregate identifier
     * @param DateTimeImmutable $timestamp The point in time
     * @return EventInterface[]
     */
    public function readStreamUntil(
        string $aggregateId,
        DateTimeImmutable $timestamp
    ): array;

    /**
     * Read events for an aggregate from a specific date
     *
     * @param string $aggregateId The aggregate identifier
     * @param DateTimeImmutable $fromDate Starting date
     * @return EventInterface[]
     */
    public function readStreamFromDate(
        string $aggregateId,
        DateTimeImmutable $fromDate
    ): array;

    /**
     * Read events for an aggregate up to a specific date
     *
     * @param string $aggregateId The aggregate identifier
     * @param DateTimeImmutable $upToDate Ending date
     * @return EventInterface[]
     */
    public function readStreamUpToDate(
        string $aggregateId,
        DateTimeImmutable $upToDate
    ): array;

    /**
     * Read all events of a specific type across all aggregates
     *
     * @param string $eventType The event type (fully qualified class name)
     * @param int|null $limit Maximum number of events to return
     * @return EventInterface[]
     */
    public function readEventsByType(string $eventType, ?int $limit = null): array;

    /**
     * Read events of a specific type within a date range
     *
     * @param string $eventType The event type
     * @param DateTimeImmutable $from Start date
     * @param DateTimeImmutable $to End date
     * @return EventInterface[]
     */
    public function readEventsByTypeAndDateRange(
        string $eventType,
        DateTimeImmutable $from,
        DateTimeImmutable $to
    ): array;
}
