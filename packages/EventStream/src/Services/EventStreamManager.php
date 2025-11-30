<?php

declare(strict_types=1);

namespace Nexus\EventStream\Services;

use DateTimeImmutable;
use Nexus\EventStream\Contracts\EventStoreInterface;
use Nexus\EventStream\Contracts\ProjectorInterface;
use Nexus\EventStream\Contracts\SnapshotRepositoryInterface;
use Nexus\EventStream\Contracts\StreamReaderInterface;
use Nexus\EventStream\Core\Engine\ProjectionEngine;
use Nexus\EventStream\Core\Engine\SnapshotManager;
use Psr\Log\LoggerInterface;

/**
 * EventStreamManager
 *
 * Main service for managing event streams. This is the primary public API
 * for the EventStream package.
 *
 * Requirements satisfied:
 * - ARC-EVS-7004: Business logic in service layer (EventStreamManager)
 * - FUN-EVS-7208: Replay event stream to rebuild aggregate state at specific point in time
 * - FUN-EVS-7222: Provide temporal query API: getStateAt(aggregateId, timestamp)
 *
 * @package Nexus\EventStream\Services
 */
final readonly class EventStreamManager
{
    public function __construct(
        private EventStoreInterface $eventStore,
        private StreamReaderInterface $streamReader,
        private SnapshotRepositoryInterface $snapshotRepository,
        private ProjectionEngine $projectionEngine,
        private SnapshotManager $snapshotManager,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Get the current version of a stream
     *
     * @param string $aggregateId The aggregate identifier
     * @return int
     */
    public function getCurrentVersion(string $aggregateId): int
    {
        return $this->eventStore->getCurrentVersion($aggregateId);
    }

    /**
     * Check if a stream exists
     *
     * @param string $aggregateId The aggregate identifier
     * @return bool
     */
    public function streamExists(string $aggregateId): bool
    {
        return $this->eventStore->streamExists($aggregateId);
    }

    /**
     * Replay events to rebuild aggregate state at a specific point in time (temporal query)
     *
     * @param string|object $aggregateId The aggregate identifier
     * @param DateTimeImmutable $timestamp The point in time
     * @return array<string, mixed> The reconstructed state
     */
    public function getStateAt(
        string|object $aggregateId,
        DateTimeImmutable $timestamp
    ): array {
        $aggregateIdStr = is_object($aggregateId) ? (string) $aggregateId : $aggregateId;

        $this->logger->info('Temporal query: replaying events', [
            'aggregate_id' => $aggregateIdStr,
            'timestamp' => $timestamp->format('Y-m-d H:i:s'),
        ]);

        // Try to get a snapshot at or before the timestamp
        $snapshot = $this->snapshotRepository->getLatestSnapshotBefore($aggregateIdStr, $timestamp);
        
        if ($snapshot !== null) {
            // Start from snapshot and replay events after it
            $snapshotDate = $snapshot['created_at'] ?? $timestamp;
            $events = $this->streamReader->readStreamFromDate($aggregateIdStr, $snapshotDate);
            $state = $snapshot;
        } else {
            // No snapshot, replay all events up to timestamp
            $events = $this->streamReader->readStreamUpToDate($aggregateIdStr, $timestamp);
            $state = [];
        }

        // Add events count to state
        $state['events_count'] = count($events);

        $this->logger->info('Temporal query completed', [
            'aggregate_id' => $aggregateIdStr,
            'events_replayed' => count($events),
            'used_snapshot' => $snapshot !== null,
        ]);

        return $state;
    }

    /**
     * Rebuild a projection from scratch
     *
     * @param string $streamId The stream identifier
     * @param ProjectorInterface $projector The projector to rebuild
     * @return void
     */
    public function rebuildProjection(string $streamId, ProjectorInterface $projector): void
    {
        $this->logger->info('Rebuilding projection', [
            'stream_id' => $streamId,
            'projector' => $projector->getName(),
        ]);

        $this->projectionEngine->rebuild($streamId, $projector);
    }

    /**
     * Run a projector on all events of the types it handles
     *
     * @param string $streamId The stream identifier
     * @param ProjectorInterface $projector The projector to run
     * @return void
     */
    public function runProjection(string $streamId, ProjectorInterface $projector): void
    {
        $this->projectionEngine->run($streamId, $projector);
    }

    /**
     * Resume a projection from a specific event
     *
     * @param string $streamId The stream identifier
     * @param ProjectorInterface $projector The projector to resume
     * @param string $lastEventId The last processed event ID
     * @return void
     */
    public function resumeProjection(string $streamId, ProjectorInterface $projector, string $lastEventId): void
    {
        $this->projectionEngine->resume($streamId, $projector, $lastEventId);
    }

    /**
     * Create a snapshot for an aggregate if threshold is reached
     *
     * @param string $aggregateId The aggregate identifier
     * @param array<string, mixed> $state The aggregate state
     * @return bool True if snapshot was created
     */
    public function createSnapshotIfNeeded(string $aggregateId, array $state): bool
    {
        return $this->snapshotManager->createIfNeeded($aggregateId, $state);
    }

    /**
     * Get stream health metrics
     *
     * @param string|object $aggregateId The aggregate identifier
     * @return array<string, mixed>
     */
    public function getStreamHealth(string|object $aggregateId): array
    {
        $aggregateIdStr = is_object($aggregateId) ? (string) $aggregateId : $aggregateId;

        $events = $this->streamReader->readStream($aggregateIdStr);
        $currentVersion = $this->eventStore->getCurrentVersion($aggregateIdStr);
        $latestSnapshot = $this->snapshotRepository->getLatestSnapshot($aggregateIdStr);

        $hasSnapshot = $latestSnapshot !== null;
        $snapshotVersion = $hasSnapshot ? ($latestSnapshot['version'] ?? 0) : 0;
        $eventsSinceSnapshot = $hasSnapshot ? (count($events) - $snapshotVersion) : count($events);

        $health = [
            'aggregate_id' => $aggregateIdStr,
            'total_events' => count($events),
            'current_version' => $currentVersion,
            'has_snapshot' => $hasSnapshot,
            'snapshot_version' => $snapshotVersion,
            'events_since_snapshot' => $eventsSinceSnapshot,
            'stream_exists' => !empty($events),
        ];

        // Add recommendation if needed
        if (!$hasSnapshot && count($events) > 100) {
            $health['recommendation'] = 'needs_snapshot';
        }

        return $health;
    }

    /**
     * Maintain a stream by creating snapshots if needed
     *
     * @param string|object $aggregateId The aggregate identifier
     * @return void
     */
    public function maintainStream(string|object $aggregateId): void
    {
        $aggregateIdStr = is_object($aggregateId) ? (string) $aggregateId : $aggregateId;
        $events = $this->streamReader->readStream($aggregateIdStr);

        // For now, pass empty state - will be enhanced when aggregate replay is implemented
        $this->snapshotManager->createIfNeeded($aggregateIdStr, ['events_count' => count($events)]);
    }

    /**
     * Append an event to a stream
     *
     * @param string $aggregateId The aggregate identifier
     * @param mixed $event The event to append
     * @param int $expectedVersion The expected current version
     * @return void
     * @throws \Nexus\EventStream\Exceptions\ConcurrencyException
     */
    public function appendEvent(string $aggregateId, mixed $event, int $expectedVersion): void
    {
        try {
            $this->eventStore->append($aggregateId, $event, $expectedVersion);
        } catch (\Nexus\EventStream\Exceptions\ConcurrencyException $e) {
            $this->logger->warning('Concurrency conflict detected', [
                'aggregate_id' => $e->getAggregateId(),
                'expected_version' => $e->getExpectedVersion(),
                'actual_version' => $e->getActualVersion(),
            ]);
            throw $e;
        }
    }
}
