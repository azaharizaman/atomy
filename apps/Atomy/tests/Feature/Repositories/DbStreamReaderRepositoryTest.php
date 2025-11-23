<?php

declare(strict_types=1);

namespace Tests\Feature\Repositories;

use App\Models\EventStream;
use App\Repositories\DbStreamReaderRepository;
use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Nexus\EventStream\Contracts\EventInterface;
use Nexus\Tenant\Contracts\TenantContextInterface;
use Tests\TestCase;

/**
 * DbStreamReaderRepository Feature Tests
 *
 * Validates the database implementation of StreamReaderInterface with:
 * - Reading full event streams by aggregate ID
 * - Version range filtering
 * - Temporal queries (date-based filtering)
 * - Event type filtering
 * - Tenant isolation
 * - Ordering and pagination
 *
 * Requirements Coverage:
 * - FUN-EVS-7202: StreamReaderInterface for reading event streams with filtering
 * - FUN-EVS-7206: Read event stream by aggregate ID with optional version range filtering
 * - FUN-EVS-7207: Read event stream by event type
 * - FUN-EVS-7208: Replay event stream to rebuild aggregate state
 * - BUS-EVS-7107: Tenant isolation
 *
 * @group EventStream
 * @group Database
 * @group PR3
 */
final class DbStreamReaderRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private DbStreamReaderRepository $repository;
    private TenantContextInterface $tenantContext;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenantContext = $this->app->make(TenantContextInterface::class);
        $this->repository = new DbStreamReaderRepository($this->tenantContext);
    }

    /** @test */
    public function it_reads_full_event_stream_for_aggregate(): void
    {
        // Arrange
        $aggregateId = 'account-100';
        $this->createTestEvents($aggregateId, [
            ['type' => 'account-created', 'version' => 1],
            ['type' => 'account-credited', 'version' => 2],
            ['type' => 'account-debited', 'version' => 3],
        ]);

        // Act
        $events = $this->repository->readStream($aggregateId);

        // Assert
        $this->assertCount(3, $events);
        $this->assertEquals('account-created', $events[0]->getEventType());
        $this->assertEquals('account-credited', $events[1]->getEventType());
        $this->assertEquals('account-debited', $events[2]->getEventType());
    }

    /** @test */
    public function it_returns_empty_array_for_non_existent_stream(): void
    {
        // Act
        $events = $this->repository->readStream('non-existent-aggregate');

        // Assert
        $this->assertCount(0, $events);
    }

    /** @test */
    public function it_orders_events_by_version(): void
    {
        // Arrange
        $aggregateId = 'account-101';
        $this->createTestEvents($aggregateId, [
            ['type' => 'event-3', 'version' => 3, 'created_at' => '2024-01-01 12:00:00'],
            ['type' => 'event-1', 'version' => 1, 'created_at' => '2024-01-01 10:00:00'],
            ['type' => 'event-2', 'version' => 2, 'created_at' => '2024-01-01 11:00:00'],
        ]);

        // Act
        $events = $this->repository->readStream($aggregateId);

        // Assert
        $this->assertCount(3, $events);
        $this->assertEquals(1, $events[0]->getVersion());
        $this->assertEquals(2, $events[1]->getVersion());
        $this->assertEquals(3, $events[2]->getVersion());
    }

    /** @test */
    public function it_reads_stream_from_specific_version(): void
    {
        // Arrange
        $aggregateId = 'account-102';
        $this->createTestEvents($aggregateId, [
            ['type' => 'event-1', 'version' => 1],
            ['type' => 'event-2', 'version' => 2],
            ['type' => 'event-3', 'version' => 3],
            ['type' => 'event-4', 'version' => 4],
            ['type' => 'event-5', 'version' => 5],
        ]);

        // Act
        $events = $this->repository->readStreamFromVersion($aggregateId, 3);

        // Assert
        $this->assertCount(3, $events);
        $this->assertEquals(3, $events[0]->getVersion());
        $this->assertEquals(4, $events[1]->getVersion());
        $this->assertEquals(5, $events[2]->getVersion());
    }

    /** @test */
    public function it_reads_stream_within_version_range(): void
    {
        // Arrange
        $aggregateId = 'account-103';
        $this->createTestEvents($aggregateId, [
            ['type' => 'event-1', 'version' => 1],
            ['type' => 'event-2', 'version' => 2],
            ['type' => 'event-3', 'version' => 3],
            ['type' => 'event-4', 'version' => 4],
            ['type' => 'event-5', 'version' => 5],
        ]);

        // Act
        $events = $this->repository->readStreamFromVersion($aggregateId, 2, 4);

        // Assert
        $this->assertCount(3, $events);
        $this->assertEquals(2, $events[0]->getVersion());
        $this->assertEquals(3, $events[1]->getVersion());
        $this->assertEquals(4, $events[2]->getVersion());
    }

    /** @test */
    public function it_reads_stream_until_specific_timestamp(): void
    {
        // Arrange
        $aggregateId = 'account-104';
        $cutoffTime = new DateTimeImmutable('2024-01-15 12:00:00');

        $this->createTestEvents($aggregateId, [
            ['type' => 'event-1', 'version' => 1, 'occurred_at' => '2024-01-15 10:00:00'],
            ['type' => 'event-2', 'version' => 2, 'occurred_at' => '2024-01-15 11:00:00'],
            ['type' => 'event-3', 'version' => 3, 'occurred_at' => '2024-01-15 12:00:00'], // Exactly at cutoff
            ['type' => 'event-4', 'version' => 4, 'occurred_at' => '2024-01-15 13:00:00'],
        ]);

        // Act
        $events = $this->repository->readStreamUntil($aggregateId, $cutoffTime);

        // Assert
        $this->assertCount(3, $events);
        $this->assertEquals('event-1', $events[0]->getEventType());
        $this->assertEquals('event-2', $events[1]->getEventType());
        $this->assertEquals('event-3', $events[2]->getEventType());
    }

    /** @test */
    public function it_reads_stream_from_specific_date(): void
    {
        // Arrange
        $aggregateId = 'account-105';
        $startDate = new DateTimeImmutable('2024-01-15 00:00:00');

        $this->createTestEvents($aggregateId, [
            ['type' => 'event-1', 'version' => 1, 'occurred_at' => '2024-01-14 23:00:00'],
            ['type' => 'event-2', 'version' => 2, 'occurred_at' => '2024-01-15 00:00:00'], // Exactly at start
            ['type' => 'event-3', 'version' => 3, 'occurred_at' => '2024-01-15 12:00:00'],
            ['type' => 'event-4', 'version' => 4, 'occurred_at' => '2024-01-16 10:00:00'],
        ]);

        // Act
        $events = $this->repository->readStreamFromDate($aggregateId, $startDate);

        // Assert
        $this->assertCount(3, $events);
        $this->assertEquals('event-2', $events[0]->getEventType());
        $this->assertEquals('event-3', $events[1]->getEventType());
        $this->assertEquals('event-4', $events[2]->getEventType());
    }

    /** @test */
    public function it_reads_stream_up_to_specific_date(): void
    {
        // Arrange
        $aggregateId = 'account-106';
        $endDate = new DateTimeImmutable('2024-01-15 23:59:59');

        $this->createTestEvents($aggregateId, [
            ['type' => 'event-1', 'version' => 1, 'occurred_at' => '2024-01-14 12:00:00'],
            ['type' => 'event-2', 'version' => 2, 'occurred_at' => '2024-01-15 12:00:00'],
            ['type' => 'event-3', 'version' => 3, 'occurred_at' => '2024-01-15 23:59:59'], // Exactly at end
            ['type' => 'event-4', 'version' => 4, 'occurred_at' => '2024-01-16 00:00:00'],
        ]);

        // Act
        $events = $this->repository->readStreamUpToDate($aggregateId, $endDate);

        // Assert
        $this->assertCount(3, $events);
        $this->assertEquals('event-1', $events[0]->getEventType());
        $this->assertEquals('event-2', $events[1]->getEventType());
        $this->assertEquals('event-3', $events[2]->getEventType());
    }

    /** @test */
    public function it_reads_events_by_type_across_aggregates(): void
    {
        // Arrange
        $this->createTestEvents('account-200', [
            ['type' => 'account-credited', 'version' => 1],
            ['type' => 'account-debited', 'version' => 2],
        ]);

        $this->createTestEvents('account-201', [
            ['type' => 'account-credited', 'version' => 1],
            ['type' => 'account-closed', 'version' => 2],
        ]);

        $this->createTestEvents('account-202', [
            ['type' => 'account-credited', 'version' => 1],
        ]);

        // Act
        $events = $this->repository->readEventsByType('account-credited');

        // Assert
        $this->assertCount(3, $events);
        foreach ($events as $event) {
            $this->assertEquals('account-credited', $event->getEventType());
        }
    }

    /** @test */
    public function it_reads_events_by_type_with_limit(): void
    {
        // Arrange
        $this->createTestEvents('account-300', [
            ['type' => 'payment-received', 'version' => 1, 'occurred_at' => '2024-01-01 10:00:00'],
        ]);

        $this->createTestEvents('account-301', [
            ['type' => 'payment-received', 'version' => 1, 'occurred_at' => '2024-01-01 11:00:00'],
        ]);

        $this->createTestEvents('account-302', [
            ['type' => 'payment-received', 'version' => 1, 'occurred_at' => '2024-01-01 12:00:00'],
        ]);

        // Act
        $events = $this->repository->readEventsByType('payment-received', 2);

        // Assert
        $this->assertCount(2, $events);
    }

    /** @test */
    public function it_reads_events_by_type_within_date_range(): void
    {
        // Arrange
        $from = new DateTimeImmutable('2024-01-10 00:00:00');
        $to = new DateTimeImmutable('2024-01-20 23:59:59');

        $this->createTestEvents('account-400', [
            ['type' => 'invoice-created', 'version' => 1, 'occurred_at' => '2024-01-05 10:00:00'], // Before range
        ]);

        $this->createTestEvents('account-401', [
            ['type' => 'invoice-created', 'version' => 1, 'occurred_at' => '2024-01-15 10:00:00'], // Within range
        ]);

        $this->createTestEvents('account-402', [
            ['type' => 'invoice-created', 'version' => 1, 'occurred_at' => '2024-01-18 10:00:00'], // Within range
        ]);

        $this->createTestEvents('account-403', [
            ['type' => 'invoice-created', 'version' => 1, 'occurred_at' => '2024-01-25 10:00:00'], // After range
        ]);

        // Act
        $events = $this->repository->readEventsByTypeAndDateRange('invoice-created', $from, $to);

        // Assert
        $this->assertCount(2, $events);
    }

    /** @test */
    public function it_isolates_streams_by_tenant(): void
    {
        // Arrange
        $aggregateId = 'shared-aggregate-id';

        $this->tenantContext->setCurrentTenant('tenant-alpha');
        $this->createTestEvents($aggregateId, [
            ['type' => 'event-alpha-1', 'version' => 1],
            ['type' => 'event-alpha-2', 'version' => 2],
        ]);

        $this->tenantContext->setCurrentTenant('tenant-beta');
        $this->createTestEvents($aggregateId, [
            ['type' => 'event-beta-1', 'version' => 1],
        ]);

        // Act
        $this->tenantContext->setCurrentTenant('tenant-alpha');
        $alphaEvents = $this->repository->readStream($aggregateId);

        $this->tenantContext->setCurrentTenant('tenant-beta');
        $betaEvents = $this->repository->readStream($aggregateId);

        // Assert
        $this->assertCount(2, $alphaEvents);
        $this->assertCount(1, $betaEvents);
        $this->assertEquals('event-alpha-1', $alphaEvents[0]->getEventType());
        $this->assertEquals('event-beta-1', $betaEvents[0]->getEventType());
    }

    /** @test */
    public function it_returns_events_implementing_event_interface(): void
    {
        // Arrange
        $aggregateId = 'account-500';
        $this->createTestEvents($aggregateId, [
            ['type' => 'test-event', 'version' => 1],
        ]);

        // Act
        $events = $this->repository->readStream($aggregateId);

        // Assert
        $this->assertCount(1, $events);
        $this->assertInstanceOf(EventInterface::class, $events[0]);
        $this->assertEquals('test-event', $events[0]->getEventType());
        $this->assertEquals(1, $events[0]->getVersion());
    }

    /** @test */
    public function it_preserves_event_metadata_when_reading(): void
    {
        // Arrange
        $aggregateId = 'account-600';
        $metadata = [
            'user_id' => 'user-123',
            'ip_address' => '192.168.1.100',
            'causation_id' => 'cmd-456',
        ];

        EventStream::create([
            'event_id' => 'evt-' . uniqid(),
            'aggregate_id' => $aggregateId,
            'aggregate_type' => 'account',
            'version' => 1,
            'event_type' => 'test-event',
            'payload' => ['amount' => 1000],
            'metadata' => $metadata,
            'tenant_id' => $this->tenantContext->getCurrentTenant(),
            'occurred_at' => now(),
        ]);

        // Act
        $events = $this->repository->readStream($aggregateId);

        // Assert
        $this->assertCount(1, $events);
        $this->assertEquals($metadata, $events[0]->getMetadata());
    }

    /** @test */
    public function it_handles_large_event_streams_efficiently(): void
    {
        // Arrange
        $aggregateId = 'account-700';
        $eventCount = 100;

        $eventsData = [];
        for ($i = 1; $i <= $eventCount; $i++) {
            $eventsData[] = [
                'type' => "event-{$i}",
                'version' => $i,
            ];
        }

        $this->createTestEvents($aggregateId, $eventsData);

        // Act
        $events = $this->repository->readStream($aggregateId);

        // Assert
        $this->assertCount($eventCount, $events);
        $this->assertEquals(1, $events[0]->getVersion());
        $this->assertEquals($eventCount, $events[$eventCount - 1]->getVersion());
    }

    /** @test */
    public function it_reads_stream_from_version_with_no_matching_events(): void
    {
        // Arrange
        $aggregateId = 'account-800';
        $this->createTestEvents($aggregateId, [
            ['type' => 'event-1', 'version' => 1],
            ['type' => 'event-2', 'version' => 2],
        ]);

        // Act
        $events = $this->repository->readStreamFromVersion($aggregateId, 10);

        // Assert
        $this->assertCount(0, $events);
    }

    /** @test */
    public function it_orders_events_by_occurred_at_when_reading_by_type(): void
    {
        // Arrange
        $this->createTestEvents('account-900', [
            ['type' => 'status-changed', 'version' => 1, 'occurred_at' => '2024-01-15 12:00:00'],
        ]);

        $this->createTestEvents('account-901', [
            ['type' => 'status-changed', 'version' => 1, 'occurred_at' => '2024-01-15 10:00:00'],
        ]);

        $this->createTestEvents('account-902', [
            ['type' => 'status-changed', 'version' => 1, 'occurred_at' => '2024-01-15 14:00:00'],
        ]);

        // Act
        $events = $this->repository->readEventsByType('status-changed');

        // Assert
        $this->assertCount(3, $events);
        $this->assertEquals('account-901', $events[0]->getAggregateId()); // Earliest
        $this->assertEquals('account-900', $events[1]->getAggregateId());
        $this->assertEquals('account-902', $events[2]->getAggregateId()); // Latest
    }

    /**
     * Helper method to create test events
     */
    private function createTestEvents(string $aggregateId, array $eventsData): void
    {
        foreach ($eventsData as $eventData) {
            EventStream::create([
                'event_id' => 'evt-' . uniqid() . '-' . mt_rand(),
                'aggregate_id' => $aggregateId,
                'aggregate_type' => explode('-', $aggregateId)[0] ?? 'unknown',
                'version' => $eventData['version'],
                'event_type' => $eventData['type'],
                'payload' => $eventData['payload'] ?? ['test' => true],
                'metadata' => $eventData['metadata'] ?? [],
                'tenant_id' => $this->tenantContext->getCurrentTenant(),
                'occurred_at' => $eventData['occurred_at'] ?? now(),
                'created_at' => $eventData['created_at'] ?? now(),
            ]);
        }
    }
}
