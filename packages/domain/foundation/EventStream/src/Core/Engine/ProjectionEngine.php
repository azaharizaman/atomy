<?php

declare(strict_types=1);

namespace Nexus\EventStream\Core\Engine;

use Nexus\EventStream\Contracts\ProjectorInterface;
use Nexus\EventStream\Contracts\StreamReaderInterface;
use Nexus\EventStream\Exceptions\ProjectionException;
use Psr\Log\LoggerInterface;

/**
 * ProjectionEngine
 *
 * Internal engine for running and managing projections.
 * This is part of the internal Core/ folder and should not be directly accessed
 * by consuming applications.
 *
 * Requirements satisfied:
 * - ARC-EVS-7010: Separate Core/ folder for internal engine (ProjectionEngine)
 * - FUN-EVS-7215: Provide projection engine for building read models
 * - FUN-EVS-7217: Rebuild projections from scratch by replaying entire stream
 * - FUN-EVS-7218: Resume projection from last processed event on failure/restart
 *
 * @package Nexus\EventStream\Core\Engine
 */
readonly class ProjectionEngine
{
    public function __construct(
        private StreamReaderInterface $streamReader,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Run a projector from its last processed position
     *
     * @param string $streamId The stream identifier
     * @param ProjectorInterface $projector The projector to run
     * @return void
     */
    public function run(string $streamId, ProjectorInterface $projector): void
    {
        $lastEventId = $projector->getLastProcessedEventId();

        $this->logger->info('Running projection', [
            'stream_id' => $streamId,
            'projector' => $projector->getName(),
            'last_event_id' => $lastEventId,
        ]);

        // Read events from the stream
        $events = $this->streamReader->readStream($streamId);
        $totalEvents = count($events);
        $projectedEvents = 0;

        foreach ($events as $event) {
            // Skip events we've already processed
            if ($lastEventId !== null && $event->getEventId() <= $lastEventId) {
                continue;
            }

            // Only project events this projector handles
            if (!in_array($event->getEventType(), $projector->getHandledEventTypes(), true)) {
                continue;
            }

            try {
                $projector->project($event);
                $projector->setLastProcessedEventId($event->getEventId());
                $projectedEvents++;
            } catch (\Throwable $e) {
                $this->logger->error('Projection error', [
                    'projector' => $projector->getName(),
                    'event_id' => $event->getEventId(),
                    'error' => $e->getMessage(),
                ]);

                // Don't throw, just log and continue with next event
                continue;
            }
        }

        $this->logger->info('Projection completed', [
            'projector' => $projector->getName(),
            'total_events' => $totalEvents,
            'projected_events' => $projectedEvents,
        ]);
    }

    /**
     * Rebuild a projector from scratch by replaying all events
     *
     * @param string $streamId The stream identifier
     * @param ProjectorInterface $projector The projector to rebuild
     * @return void
     */
    public function rebuild(string $streamId, ProjectorInterface $projector): void
    {
        $this->logger->info('Rebuilding projection from scratch', [
            'stream_id' => $streamId,
            'projector' => $projector->getName(),
        ]);

        // Reset the projector
        $projector->reset();

        // Run from the beginning
        $this->run($streamId, $projector);
    }

    /**
     * Resume a projection from a specific event ID
     *
     * @param string $streamId The stream identifier
     * @param ProjectorInterface $projector The projector to resume
     * @param string $lastEventId The last processed event ID
     * @return void
     */
    public function resume(string $streamId, ProjectorInterface $projector, string $lastEventId): void
    {
        $projector->setLastProcessedEventId($lastEventId);
        $this->run($streamId, $projector);
    }
}
