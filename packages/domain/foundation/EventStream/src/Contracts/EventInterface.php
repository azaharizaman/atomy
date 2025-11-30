<?php

declare(strict_types=1);

namespace Nexus\EventStream\Contracts;

use DateTimeImmutable;

/**
 * EventInterface
 *
 * Represents an immutable domain event in the event stream.
 * All domain events must implement this interface.
 *
 * Requirements satisfied:
 * - ARC-EVS-7002: All data structures defined via interfaces
 * - BUS-EVS-7104: Each event MUST contain aggregate ID, event type, version, timestamp, payload
 *
 * @package Nexus\EventStream\Contracts
 */
interface EventInterface
{
    /**
     * Get the unique event identifier (ULID)
     *
     * @return string
     */
    public function getEventId(): string;

    /**
     * Get the aggregate ID this event belongs to
     *
     * @return string
     */
    public function getAggregateId(): string;

    /**
     * Get the event type (fully qualified class name)
     *
     * @return string
     */
    public function getEventType(): string;

    /**
     * Get the event version number (for optimistic concurrency control)
     *
     * @return int
     */
    public function getVersion(): int;

    /**
     * Get the timestamp when the event occurred
     *
     * @return DateTimeImmutable
     */
    public function getOccurredAt(): DateTimeImmutable;

    /**
     * Get the event payload (serializable data)
     *
     * @return array<string, mixed>
     */
    public function getPayload(): array;

    /**
     * Get causation ID (the event that triggered this event)
     *
     * @return string|null
     */
    public function getCausationId(): ?string;

    /**
     * Get correlation ID (for distributed tracing)
     *
     * @return string|null
     */
    public function getCorrelationId(): ?string;

    /**
     * Get tenant ID for multi-tenancy isolation
     *
     * @return string
     */
    public function getTenantId(): string;

    /**
     * Get user ID who triggered the event
     *
     * @return string|null
     */
    public function getUserId(): ?string;

    /**
     * Get event metadata (additional context)
     *
     * @return array<string, mixed>
     */
    public function getMetadata(): array;
}
