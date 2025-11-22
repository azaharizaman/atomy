<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Infrastructure\EventStream;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Nexus\EventStream\Contracts\EventStoreInterface;
use Nexus\EventStream\Contracts\StreamReaderInterface;

final readonly class EventStreamController extends Controller
{
    public function __construct(
        private EventStoreInterface $eventStore,
        private StreamReaderInterface $streamReader
    ) {}
    
    /**
     * Append an event to the stream
     */
    public function append(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'aggregate_id' => 'required|string|max:26',
            'event_type' => 'required|string|max:255',
            'payload' => 'required|array',
            'expected_version' => 'nullable|integer|min:0',
        ]);
        
        $event = EventStream::factory()->make([
            'aggregate_id' => $validated['aggregate_id'],
            'event_type' => $validated['event_type'],
            'payload' => $validated['payload'],
            'version' => $this->eventStore->getCurrentVersion($validated['aggregate_id']) + 1,
            'tenant_id' => auth()->user()->tenant_id ?? 'default',
            'user_id' => auth()->id(),
        ]);
        
        $this->eventStore->append(
            $validated['aggregate_id'],
            $event,
            $validated['expected_version'] ?? null
        );
        
        return response()->json([
            'message' => 'Event appended successfully',
            'data' => [
                'event_id' => $event->getEventId(),
                'aggregate_id' => $event->getAggregateId(),
                'version' => $event->getVersion(),
            ],
        ], 201);
    }
    
    /**
     * Read all events for an aggregate
     */
    public function read(string $aggregateId): JsonResponse
    {
        $events = $this->streamReader->readStream($aggregateId);
        
        return response()->json([
            'data' => array_map(fn($event) => [
                'event_id' => $event->getEventId(),
                'aggregate_id' => $event->getAggregateId(),
                'event_type' => $event->getEventType(),
                'version' => $event->getVersion(),
                'occurred_at' => $event->getOccurredAt()->format('Y-m-d H:i:s'),
                'payload' => $event->getPayload(),
            ], $events),
        ]);
    }
    
    /**
     * Read events by type
     */
    public function readByType(string $eventType, Request $request): JsonResponse
    {
        $limit = $request->query('limit', 100);
        $events = $this->streamReader->readEventsByType($eventType, $limit);
        
        return response()->json([
            'data' => array_map(fn($event) => [
                'event_id' => $event->getEventId(),
                'aggregate_id' => $event->getAggregateId(),
                'event_type' => $event->getEventType(),
                'version' => $event->getVersion(),
                'occurred_at' => $event->getOccurredAt()->format('Y-m-d H:i:s'),
                'payload' => $event->getPayload(),
            ], $events),
        ]);
    }
}
