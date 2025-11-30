<?php

declare(strict_types=1);

namespace Nexus\EventStream\Contracts;

/**
 * Projection State Repository Interface
 *
 * Manages projection state persistence (last processed event ID and timestamp).
 * Must support atomic updates to prevent replay position corruption.
 *
 * Requirements satisfied:
 * - FUN-EVS-7212: Track projection state (last event ID, timestamp)
 * - PER-EVS-7313: Optimize projection resume from checkpoint
 *
 * @package Nexus\EventStream\Contracts
 */
interface ProjectionStateRepositoryInterface
{
    /**
     * Get the last processed event ID for a projector.
     *
     * @param string $projectorName Unique projector identifier
     * @return string|null Event ID, or null if no state exists
     */
    public function getLastProcessedEventId(string $projectorName): ?string;

    /**
     * Get the timestamp when projection last processed an event.
     *
     * @param string $projectorName Unique projector identifier
     * @return \DateTimeImmutable|null Timestamp, or null if no state exists
     */
    public function getLastProcessedAt(string $projectorName): ?\DateTimeImmutable;

    /**
     * Save projection state atomically.
     *
     * CRITICAL: This must be atomic to prevent corruption.
     *
     * @param string $projectorName Unique projector identifier
     * @param string $eventId Last processed event ID
     * @param \DateTimeImmutable $processedAt Processing timestamp
     * @return void
     */
    public function saveState(
        string $projectorName,
        string $eventId,
        \DateTimeImmutable $processedAt
    ): void;

    /**
     * Reset projection state (clear checkpoint).
     *
     * @param string $projectorName Unique projector identifier
     * @return void
     */
    public function resetState(string $projectorName): void;

    /**
     * Check if projection state exists.
     *
     * @param string $projectorName Unique projector identifier
     * @return bool True if state exists, false otherwise
     */
    public function hasState(string $projectorName): bool;
}
