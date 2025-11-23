<?php

declare(strict_types=1);

namespace Nexus\EventStream\Contracts;

/**
 * Projection Manager Interface
 *
 * Orchestrates projection rebuilds with pessimistic locking and state management.
 * Ensures projections can be rebuilt safely without race conditions.
 *
 * Requirements satisfied:
 * - FUN-EVS-7212: Projection management and orchestration
 * - FUN-EVS-7218: Projection rebuilds with pessimistic locks
 * - PER-EVS-7313: Resume from checkpoint for efficiency
 * - REL-EVS-7413: Prevent concurrent projection rebuilds
 *
 * @package Nexus\EventStream\Contracts
 */
interface ProjectionManagerInterface
{
    /**
     * Rebuild a projection from scratch.
     *
     * Acquires lock, resets projection state, replays all events, releases lock.
     *
     * @param ProjectorInterface $projector Projector to rebuild
     * @param int $batchSize Number of events to process per batch
     * @return array{processed: int, duration: float, from_event: string|null, to_event: string|null}
     * @throws \Nexus\EventStream\Exceptions\ProjectionLockedException If already locked
     * @throws \Nexus\EventStream\Exceptions\ProjectionException If rebuild fails
     */
    public function rebuild(ProjectorInterface $projector, int $batchSize = 100): array;

    /**
     * Resume a projection from last checkpoint.
     *
     * Processes only events after the last processed event ID.
     *
     * @param ProjectorInterface $projector Projector to resume
     * @param int $batchSize Number of events to process per batch
     * @return array{processed: int, duration: float, from_event: string|null, to_event: string|null}
     * @throws \Nexus\EventStream\Exceptions\ProjectionLockedException If already locked
     * @throws \Nexus\EventStream\Exceptions\ProjectionException If resume fails
     */
    public function resume(ProjectorInterface $projector, int $batchSize = 100): array;

    /**
     * Get projection status.
     *
     * @param string $projectorName Projector name
     * @return array{
     *     is_locked: bool,
     *     last_event_id: string|null,
     *     last_processed_at: \DateTimeImmutable|null,
     *     lock_age_seconds: int|null
     * }
     */
    public function getStatus(string $projectorName): array;

    /**
     * Force-reset a projection (clear state and unlock).
     *
     * USE WITH EXTREME CAUTION - only for recovering from stuck projections.
     *
     * @param string $projectorName Projector name
     * @return void
     */
    public function forceReset(string $projectorName): void;
}
