<?php

declare(strict_types=1);

namespace App\Repositories\Infrastructure;

use App\Models\Infrastructure\EventStream;
use Illuminate\Support\Facades\DB;
use Nexus\EventStream\Contracts\EventInterface;
use Nexus\EventStream\Contracts\EventStoreInterface;
use Nexus\EventStream\Contracts\StreamReaderInterface;
use Nexus\EventStream\Exceptions\ConcurrencyException;
use Nexus\EventStream\Exceptions\EventStreamException;

final readonly class EloquentEventStore implements EventStoreInterface, StreamReaderInterface
{
    public function __construct()
    {
        // All dependencies injected as interfaces
    }
    
    /**
     * Append a single event to the stream
     */
    public function append(
        string $aggregateId,
        EventInterface $event,
        ?int $expectedVersion = null
    ): void {
        DB::transaction(function () use ($aggregateId, $event, $expectedVersion) {
            // Get current version
            $currentVersion = $this->getCurrentVersion($aggregateId);
            
            // Optimistic concurrency check
            if ($expectedVersion !== null && $currentVersion !== $expectedVersion) {
                throw new ConcurrencyException(
                    "Concurrency conflict: expected version {$expectedVersion}, but current version is {$currentVersion}"
                );
            }
            
            // Create event record
            EventStream::create([
                'event_id' => $event->getEventId(),
                'aggregate_id' => $event->getAggregateId(),
                'event_type' => $event->getEventType(),
                'version' => $event->getVersion(),
                'occurred_at' => $event->getOccurredAt(),
                'payload' => $event->getPayload(),
                'causation_id' => $event->getCausationId(),
                'correlation_id' => $event->getCorrelationId(),
                'tenant_id' => $event->getTenantId(),
                'user_id' => $event->getUserId(),
                'metadata' => $event->getMetadata(),
            ]);
        });
    }
    
    /**
     * Append multiple events to the stream in a single transaction
     */
    public function appendBatch(
        string $aggregateId,
        array $events,
        ?int $expectedVersion = null
    ): void {
        if (empty($events)) {
            return;
        }
        
        DB::transaction(function () use ($aggregateId, $events, $expectedVersion) {
            // Get current version
            $currentVersion = $this->getCurrentVersion($aggregateId);
            
            // Optimistic concurrency check
            if ($expectedVersion !== null && $currentVersion !== $expectedVersion) {
                throw new ConcurrencyException(
                    "Concurrency conflict: expected version {$expectedVersion}, but current version is {$currentVersion}"
                );
            }
            
            // Insert all events
            foreach ($events as $event) {
                if (!$event instanceof EventInterface) {
                    throw new EventStreamException('All events must implement EventInterface');
                }
                
                EventStream::create([
                    'event_id' => $event->getEventId(),
                    'aggregate_id' => $event->getAggregateId(),
                    'event_type' => $event->getEventType(),
                    'version' => $event->getVersion(),
                    'occurred_at' => $event->getOccurredAt(),
                    'payload' => $event->getPayload(),
                    'causation_id' => $event->getCausationId(),
                    'correlation_id' => $event->getCorrelationId(),
                    'tenant_id' => $event->getTenantId(),
                    'user_id' => $event->getUserId(),
                    'metadata' => $event->getMetadata(),
                ]);
            }
        });
    }
    
    /**
     * Get the current version of the stream
     */
    public function getCurrentVersion(string $aggregateId): int
    {
        $maxVersion = EventStream::where('aggregate_id', $aggregateId)
            ->max('version');
            
        return $maxVersion ?? 0;
    }
    
    /**
     * Check if a stream exists
     */
    public function streamExists(string $aggregateId): bool
    {
        return EventStream::where('aggregate_id', $aggregateId)->exists();
    }
    
    /**
     * Read all events for an aggregate
     */
    public function readStream(string $aggregateId): array
    {
        return EventStream::where('aggregate_id', $aggregateId)
            ->orderBy('version')
            ->get()
            ->all();
    }
    
    /**
     * Read events for an aggregate within a version range
     */
    public function readStreamFromVersion(
        string $aggregateId,
        int $fromVersion,
        ?int $toVersion = null
    ): array {
        $query = EventStream::where('aggregate_id', $aggregateId)
            ->where('version', '>=', $fromVersion);
            
        if ($toVersion !== null) {
            $query->where('version', '<=', $toVersion);
        }
        
        return $query->orderBy('version')->get()->all();
    }
    
    /**
     * Read events for an aggregate up to a specific timestamp (temporal query)
     */
    public function readStreamUntil(
        string $aggregateId,
        \DateTimeImmutable $timestamp
    ): array {
        return EventStream::where('aggregate_id', $aggregateId)
            ->where('occurred_at', '<=', $timestamp)
            ->orderBy('version')
            ->get()
            ->all();
    }
    
    /**
     * Read all events of a specific type across all aggregates
     */
    public function readEventsByType(string $eventType, ?int $limit = null): array
    {
        $query = EventStream::where('event_type', $eventType)
            ->orderBy('occurred_at');
            
        if ($limit !== null) {
            $query->limit($limit);
        }
        
        return $query->get()->all();
    }
    
    /**
     * Read events of a specific type within a date range
     */
    public function readEventsByTypeAndDateRange(
        string $eventType,
        \DateTimeImmutable $from,
        \DateTimeImmutable $to
    ): array {
        return EventStream::where('event_type', $eventType)
            ->whereBetween('occurred_at', [$from, $to])
            ->orderBy('occurred_at')
            ->get()
            ->all();
    }
}
