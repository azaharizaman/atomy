<?php

declare(strict_types=1);

namespace Nexus\EventStream\Services;

use Nexus\EventStream\Contracts\EventQueryInterface;
use Nexus\EventStream\Contracts\ProjectionLockInterface;
use Nexus\EventStream\Contracts\ProjectionManagerInterface;
use Nexus\EventStream\Contracts\ProjectionStateRepositoryInterface;
use Nexus\EventStream\Contracts\ProjectorInterface;
use Nexus\EventStream\Exceptions\ProjectionLockedException;
use Psr\Log\LoggerInterface;

/**
 * Default Projection Manager
 *
 * Orchestrates projection rebuilds with pessimistic locking and checkpoint resume.
 *
 * Features:
 * - Pessimistic locking to prevent concurrent rebuilds
 * - Checkpoint-based resume for efficiency
 * - Batch processing to prevent memory exhaustion
 * - Comprehensive error handling and logging
 *
 * Requirements satisfied:
 * - FUN-EVS-7212: Projection management and orchestration
 * - FUN-EVS-7218: Projection rebuilds with pessimistic locks
 * - PER-EVS-7313: Resume from checkpoint for efficiency
 * - REL-EVS-7413: Prevent concurrent projection rebuilds
 *
 * @package Nexus\EventStream\Services
 */
final readonly class DefaultProjectionManager implements ProjectionManagerInterface
{
    public function __construct(
        private EventQueryInterface $eventQuery,
        private ProjectionLockInterface $lock,
        private ProjectionStateRepositoryInterface $stateRepository,
        private LoggerInterface $logger
    ) {}

    /**
     * {@inheritDoc}
     */
    public function rebuild(ProjectorInterface $projector, int $batchSize = 100): array
    {
        $projectorName = $projector->getName();
        $startTime = microtime(true);

        // Attempt to acquire lock
        if (!$this->lock->acquire($projectorName)) {
            $lockAge = $this->lock->getLockAge($projectorName) ?? 0;
            throw ProjectionLockedException::alreadyLocked($projectorName, $lockAge);
        }

        try {
            $this->logger->info("Starting projection rebuild: {$projectorName}");

            // Reset projection and state
            $projector->reset();
            $this->stateRepository->resetState($projectorName);

            // Rebuild from scratch
            $stats = $this->processEvents($projector, null, $batchSize);

            $duration = microtime(true) - $startTime;
            $this->logger->info(
                "Completed projection rebuild: {$projectorName}",
                ['processed' => $stats['processed'], 'duration' => $duration]
            );

            return [
                'processed' => $stats['processed'],
                'duration' => $duration,
                'from_event' => $stats['from_event'],
                'to_event' => $stats['to_event'],
            ];
        } finally {
            $this->lock->release($projectorName);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function resume(ProjectorInterface $projector, int $batchSize = 100): array
    {
        $projectorName = $projector->getName();
        $startTime = microtime(true);

        // Attempt to acquire lock
        if (!$this->lock->acquire($projectorName)) {
            $lockAge = $this->lock->getLockAge($projectorName) ?? 0;
            throw ProjectionLockedException::alreadyLocked($projectorName, $lockAge);
        }

        try {
            // Get last checkpoint
            $lastEventId = $this->stateRepository->getLastProcessedEventId($projectorName);

            if ($lastEventId === null) {
                $this->logger->info("No checkpoint found, rebuilding from scratch: {$projectorName}");
                
                // Release lock and delegate to rebuild
                $this->lock->release($projectorName);
                return $this->rebuild($projector, $batchSize);
            }

            $this->logger->info(
                "Resuming projection from checkpoint: {$projectorName}",
                ['last_event_id' => $lastEventId]
            );

            // Resume from checkpoint
            $stats = $this->processEvents($projector, $lastEventId, $batchSize);

            $duration = microtime(true) - $startTime;
            $this->logger->info(
                "Completed projection resume: {$projectorName}",
                ['processed' => $stats['processed'], 'duration' => $duration]
            );

            return [
                'processed' => $stats['processed'],
                'duration' => $duration,
                'from_event' => $stats['from_event'],
                'to_event' => $stats['to_event'],
            ];
        } finally {
            $this->lock->release($projectorName);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getStatus(string $projectorName): array
    {
        return [
            'is_locked' => $this->lock->isLocked($projectorName),
            'last_event_id' => $this->stateRepository->getLastProcessedEventId($projectorName),
            'last_processed_at' => $this->stateRepository->getLastProcessedAt($projectorName),
            'lock_age_seconds' => $this->lock->getLockAge($projectorName),
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function forceReset(string $projectorName): void
    {
        $this->logger->warning("Force-resetting projection: {$projectorName}");

        $this->lock->forceRelease($projectorName);
        $this->stateRepository->resetState($projectorName);
    }

    /**
     * Process events in batches.
     *
     * @param ProjectorInterface $projector Projector to run
     * @param string|null $afterEventId Start after this event ID (null = from beginning)
     * @param int $batchSize Batch size
     * @return array{processed: int, from_event: string|null, to_event: string|null}
     */
    private function processEvents(
        ProjectorInterface $projector,
        ?string $afterEventId,
        int $batchSize
    ): array {
        $handledTypes = $projector->getHandledEventTypes();
        $processed = 0;
        $firstEventId = null;
        $lastEventId = null;
        $cursor = null;

        do {
            // Build query for event types this projector handles
            $query = $this->eventQuery;

            // Filter by event types
            if (!empty($handledTypes)) {
                $query = $query->whereIn('event_type', $handledTypes);
            }

            // Resume from checkpoint
            if ($afterEventId !== null) {
                $query = $query->where('event_id', '>', $afterEventId);
            }

            // Order by sequence and apply cursor
            $query = $query->orderBy('sequence', 'asc');

            if ($cursor !== null) {
                $query = $query->withCursor($cursor, $batchSize);
            } else {
                $query = $query->withCursor(null, $batchSize);
            }

            // Execute query
            $result = $query->execute();
            $events = $result->getEvents();

            // Process batch
            foreach ($events as $event) {
                $projector->project($event);

                if ($firstEventId === null) {
                    $firstEventId = $event->getEventId();
                }
                $lastEventId = $event->getEventId();
                $processed++;

                // Save checkpoint after each event
                $this->stateRepository->saveState(
                    $projector->getName(),
                    $event->getEventId(),
                    new \DateTimeImmutable()
                );
            }

            // Get cursor for next batch
            $cursor = $result->getNextCursor();

        } while ($result->hasMore());

        return [
            'processed' => $processed,
            'from_event' => $firstEventId,
            'to_event' => $lastEventId,
        ];
    }
}
