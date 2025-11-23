<?php

declare(strict_types=1);

namespace Tests\Feature\Repositories;

use App\Models\EventStream;
use App\Repositories\DbEventStoreRepository;
use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Nexus\AuditLogger\Contracts\AuditLogManagerInterface;
use Nexus\EventStream\Contracts\EventInterface;
use Nexus\EventStream\Exceptions\ConcurrencyException;
use Nexus\EventStream\Exceptions\DuplicateEventException;
use Nexus\EventStream\Exceptions\EventStreamException;
use Nexus\Tenant\Contracts\TenantContextInterface;
use Psr\Log\LoggerInterface;
use Tests\TestCase;

/**
 * DbEventStoreRepository Feature Tests
 *
 * Validates the database implementation of EventStoreInterface with:
 * - Single and batch event appending
 * - Optimistic concurrency control
 * - Transaction atomicity
 * - Tenant isolation
 * - Metadata serialization
 * - AuditLogger integration
 * - Error handling
 *
 * Requirements Coverage:
 * - FUN-EVS-7201: EventStoreInterface implementation
 * - FUN-EVS-7205: Optimistic concurrency control
 * - BUS-EVS-7105: Version conflict detection
 * - BUS-EVS-7107: Tenant isolation
 *
 * @group EventStream
 * @group Database
 * @group PR3
 */
final class DbEventStoreRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private DbEventStoreRepository $repository;
    private AuditLogManagerInterface $auditLogger;
    private LoggerInterface $logger;
    private TenantContextInterface $tenantContext;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenantContext = $this->app->make(TenantContextInterface::class);
        $this->auditLogger = $this->createMock(AuditLogManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->repository = new DbEventStoreRepository(
            $this->tenantContext,
            $this->auditLogger,
            $this->logger
        );
    }

    /** @test */
    public function it_appends_single_event_to_new_stream(): void
    {
        // Arrange
        $aggregateId = 'account-123';
        $event = $this->createMockEvent('account-credited', ['amount' => 1000]);

        $this->auditLogger->expects($this->once())
            ->method('log')
            ->with(
                $aggregateId,
                'event_appended',
                $this->stringContains('account-credited')
            );

        // Act
        $this->repository->append($aggregateId, $event);

        // Assert
        $this->assertDatabaseHas('event_streams', [
            'aggregate_id' => $aggregateId,
            'event_type' => 'account-credited',
            'version' => 1,
        ]);
    }

    /** @test */
    public function it_appends_event_with_correct_sequence_and_version(): void
    {
        // Arrange
        $aggregateId = 'account-456';
        $event1 = $this->createMockEvent('account-created', ['name' => 'Cash']);
        $event2 = $this->createMockEvent('account-credited', ['amount' => 500]);

        // Act
        $this->repository->append($aggregateId, $event1);
        $this->repository->append($aggregateId, $event2);

        // Assert
        $events = EventStream::where('aggregate_id', $aggregateId)
            ->orderBy('version')
            ->get();

        $this->assertCount(2, $events);
        $this->assertEquals(1, $events[0]->version);
        $this->assertEquals(2, $events[1]->version);
    }

    /** @test */
    public function it_enforces_optimistic_concurrency_control(): void
    {
        // Arrange
        $aggregateId = 'account-789';
        $event1 = $this->createMockEvent('account-created', ['name' => 'Revenue']);
        $event2 = $this->createMockEvent('account-credited', ['amount' => 2000]);

        $this->repository->append($aggregateId, $event1);

        // Act & Assert
        $this->expectException(ConcurrencyException::class);
        $this->expectExceptionMessage('version conflict');

        // Expect version 0, but actual is 1
        $this->repository->append($aggregateId, $event2, 0);
    }

    /** @test */
    public function it_allows_append_when_expected_version_matches(): void
    {
        // Arrange
        $aggregateId = 'account-101';
        $event1 = $this->createMockEvent('account-created', ['name' => 'Bank']);
        $event2 = $this->createMockEvent('account-credited', ['amount' => 3000]);

        $this->repository->append($aggregateId, $event1);

        // Act
        $this->repository->append($aggregateId, $event2, 1); // Expected version = 1

        // Assert
        $this->assertDatabaseHas('event_streams', [
            'aggregate_id' => $aggregateId,
            'version' => 2,
        ]);
    }

    /** @test */
    public function it_appends_batch_events_atomically(): void
    {
        // Arrange
        $aggregateId = 'account-202';
        $events = [
            $this->createMockEvent('account-created', ['name' => 'Petty Cash']),
            $this->createMockEvent('account-credited', ['amount' => 100]),
            $this->createMockEvent('account-debited', ['amount' => 50]),
        ];

        // Act
        $this->repository->appendBatch($aggregateId, $events);

        // Assert
        $storedEvents = EventStream::where('aggregate_id', $aggregateId)
            ->orderBy('version')
            ->get();

        $this->assertCount(3, $storedEvents);
        $this->assertEquals(1, $storedEvents[0]->version);
        $this->assertEquals(2, $storedEvents[1]->version);
        $this->assertEquals(3, $storedEvents[2]->version);
    }

    /** @test */
    public function it_rolls_back_batch_on_concurrency_conflict(): void
    {
        // Arrange
        $aggregateId = 'account-303';
        $event1 = $this->createMockEvent('account-created', ['name' => 'Sales']);

        $this->repository->append($aggregateId, $event1);

        $batchEvents = [
            $this->createMockEvent('account-credited', ['amount' => 500]),
            $this->createMockEvent('account-debited', ['amount' => 200]),
        ];

        // Act & Assert
        $this->expectException(ConcurrencyException::class);

        // Expected version 0, but actual is 1 - should rollback entire batch
        $this->repository->appendBatch($aggregateId, $batchEvents, 0);

        // Verify no events from batch were committed
        $count = EventStream::where('aggregate_id', $aggregateId)->count();
        $this->assertEquals(1, $count); // Only the initial event
    }

    /** @test */
    public function it_prevents_duplicate_event_ids(): void
    {
        // Arrange
        $aggregateId = 'account-404';
        $eventId = 'evt-' . uniqid();
        $event1 = $this->createMockEvent('account-created', ['name' => 'Assets'], $eventId);
        $event2 = $this->createMockEvent('account-credited', ['amount' => 1000], $eventId); // Same ID

        $this->repository->append($aggregateId, $event1);

        // Act & Assert
        $this->expectException(DuplicateEventException::class);
        $this->expectExceptionMessage('duplicate event');

        $this->repository->append($aggregateId, $event2);
    }

    /** @test */
    public function it_isolates_events_by_tenant(): void
    {
        // Arrange
        $aggregateId = 'account-505';
        $tenantId1 = 'tenant-alpha';
        $tenantId2 = 'tenant-beta';

        $event1 = $this->createMockEvent('account-created', ['name' => 'Cash']);
        $event2 = $this->createMockEvent('account-created', ['name' => 'Revenue']);

        // Act
        $this->tenantContext->setCurrentTenant($tenantId1);
        $this->repository->append($aggregateId, $event1);

        $this->tenantContext->setCurrentTenant($tenantId2);
        $this->repository->append($aggregateId, $event2);

        // Assert
        $tenant1Events = EventStream::where('tenant_id', $tenantId1)->count();
        $tenant2Events = EventStream::where('tenant_id', $tenantId2)->count();

        $this->assertEquals(1, $tenant1Events);
        $this->assertEquals(1, $tenant2Events);
    }

    /** @test */
    public function it_allows_same_aggregate_id_across_tenants(): void
    {
        // Arrange
        $aggregateId = 'account-606';
        $event = $this->createMockEvent('account-created', ['name' => 'Shared ID']);

        // Act
        $this->tenantContext->setCurrentTenant('tenant-alpha');
        $this->repository->append($aggregateId, $event);

        $version1 = $this->repository->getCurrentVersion($aggregateId);

        $this->tenantContext->setCurrentTenant('tenant-beta');
        $this->repository->append($aggregateId, $event);

        $version2 = $this->repository->getCurrentVersion($aggregateId);

        // Assert
        $this->assertEquals(1, $version1);
        $this->assertEquals(1, $version2); // Independent versioning per tenant
    }

    /** @test */
    public function it_serializes_event_payload_correctly(): void
    {
        // Arrange
        $aggregateId = 'account-707';
        $payload = [
            'amount' => 1500,
            'currency' => 'MYR',
            'description' => 'Payment received',
            'metadata' => [
                'invoice_id' => 'INV-001',
                'customer_id' => 'CUST-123',
            ],
        ];
        $event = $this->createMockEvent('payment-received', $payload);

        // Act
        $this->repository->append($aggregateId, $event);

        // Assert
        $stored = EventStream::where('aggregate_id', $aggregateId)->first();
        $decoded = json_decode($stored->payload, true);

        $this->assertEquals($payload, $decoded);
    }

    /** @test */
    public function it_serializes_event_metadata_correctly(): void
    {
        // Arrange
        $aggregateId = 'account-808';
        $metadata = [
            'user_id' => 'user-456',
            'ip_address' => '192.168.1.100',
            'causation_id' => 'cmd-789',
            'correlation_id' => 'req-012',
        ];
        $event = $this->createMockEvent('account-updated', ['name' => 'New Name']);
        $event->method('getMetadata')->willReturn($metadata);

        // Act
        $this->repository->append($aggregateId, $event);

        // Assert
        $stored = EventStream::where('aggregate_id', $aggregateId)->first();
        $decoded = json_decode($stored->metadata, true);

        $this->assertEquals($metadata, $decoded);
    }

    /** @test */
    public function it_stores_occurred_at_timestamp(): void
    {
        // Arrange
        $aggregateId = 'account-909';
        $occurredAt = new DateTimeImmutable('2024-01-15 10:30:00');
        $event = $this->createMockEvent('account-created', ['name' => 'Test']);
        $event->method('getOccurredAt')->willReturn($occurredAt);

        // Act
        $this->repository->append($aggregateId, $event);

        // Assert
        $stored = EventStream::where('aggregate_id', $aggregateId)->first();
        $this->assertEquals(
            $occurredAt->format('Y-m-d H:i:s'),
            $stored->occurred_at->format('Y-m-d H:i:s')
        );
    }

    /** @test */
    public function it_logs_append_operation_to_audit_logger(): void
    {
        // Arrange
        $aggregateId = 'account-010';
        $event = $this->createMockEvent('account-credited', ['amount' => 750]);

        $this->auditLogger->expects($this->once())
            ->method('log')
            ->with(
                $aggregateId,
                'event_appended',
                $this->callback(function (string $description) {
                    return str_contains($description, 'account-credited')
                        && str_contains($description, 'version 1');
                })
            );

        // Act
        $this->repository->append($aggregateId, $event);
    }

    /** @test */
    public function it_logs_batch_append_to_audit_logger(): void
    {
        // Arrange
        $aggregateId = 'account-011';
        $events = [
            $this->createMockEvent('account-created', ['name' => 'Batch Test']),
            $this->createMockEvent('account-credited', ['amount' => 200]),
        ];

        $this->auditLogger->expects($this->once())
            ->method('log')
            ->with(
                $aggregateId,
                'events_batch_appended',
                $this->callback(function (string $description) {
                    return str_contains($description, '2 events');
                })
            );

        // Act
        $this->repository->appendBatch($aggregateId, $events);
    }

    /** @test */
    public function it_returns_current_version_of_existing_stream(): void
    {
        // Arrange
        $aggregateId = 'account-012';
        $events = [
            $this->createMockEvent('account-created', ['name' => 'Version Test']),
            $this->createMockEvent('account-credited', ['amount' => 100]),
            $this->createMockEvent('account-debited', ['amount' => 50]),
        ];

        $this->repository->appendBatch($aggregateId, $events);

        // Act
        $version = $this->repository->getCurrentVersion($aggregateId);

        // Assert
        $this->assertEquals(3, $version);
    }

    /** @test */
    public function it_returns_zero_for_non_existent_stream(): void
    {
        // Arrange
        $aggregateId = 'non-existent-stream';

        // Act
        $version = $this->repository->getCurrentVersion($aggregateId);

        // Assert
        $this->assertEquals(0, $version);
    }

    /** @test */
    public function it_checks_stream_existence(): void
    {
        // Arrange
        $aggregateId = 'account-013';
        $event = $this->createMockEvent('account-created', ['name' => 'Existence Test']);

        // Act & Assert
        $this->assertFalse($this->repository->streamExists($aggregateId));

        $this->repository->append($aggregateId, $event);

        $this->assertTrue($this->repository->streamExists($aggregateId));
    }

    /** @test */
    public function it_respects_tenant_isolation_for_stream_existence(): void
    {
        // Arrange
        $aggregateId = 'account-014';
        $event = $this->createMockEvent('account-created', ['name' => 'Tenant Test']);

        $this->tenantContext->setCurrentTenant('tenant-alpha');
        $this->repository->append($aggregateId, $event);

        // Act & Assert
        $this->assertTrue($this->repository->streamExists($aggregateId));

        $this->tenantContext->setCurrentTenant('tenant-beta');
        $this->assertFalse($this->repository->streamExists($aggregateId));
    }

    /** @test */
    public function it_logs_errors_when_append_fails(): void
    {
        // Arrange
        $aggregateId = 'account-015';
        $event = $this->createMockEvent('account-created', ['name' => 'Error Test']);

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                $this->stringContains('Failed to append event'),
                $this->callback(function (array $context) use ($aggregateId) {
                    return $context['aggregate_id'] === $aggregateId;
                })
            );

        // Force a database error by closing connection
        DB::disconnect();

        // Act & Assert
        $this->expectException(EventStreamException::class);

        $this->repository->append($aggregateId, $event);
    }

    /** @test */
    public function it_queries_events_with_filters(): void
    {
        // Arrange
        $aggregateId = 'account-016';
        $events = [
            $this->createMockEvent('account-created', ['name' => 'Query Test']),
            $this->createMockEvent('account-credited', ['amount' => 100]),
            $this->createMockEvent('account-debited', ['amount' => 50]),
        ];

        $this->repository->appendBatch($aggregateId, $events);

        // Act
        $results = $this->repository->query(
            ['aggregate_id' => ['operator' => '=', 'value' => $aggregateId]],
            [],
            'sequence',
            'asc',
            10
        );

        // Assert
        $this->assertCount(3, $results);
    }

    /** @test */
    public function it_counts_events_with_filters(): void
    {
        // Arrange
        $aggregateId = 'account-017';
        $events = [
            $this->createMockEvent('account-created', ['name' => 'Count Test']),
            $this->createMockEvent('account-credited', ['amount' => 100]),
        ];

        $this->repository->appendBatch($aggregateId, $events);

        // Act
        $count = $this->repository->count(
            ['aggregate_id' => $aggregateId],
            []
        );

        // Assert
        $this->assertEquals(2, $count);
    }

    /**
     * Create a mock event for testing
     */
    private function createMockEvent(
        string $type,
        array $payload,
        ?string $eventId = null
    ): EventInterface {
        $event = $this->createMock(EventInterface::class);

        $event->method('getEventId')->willReturn($eventId ?? 'evt-' . uniqid());
        $event->method('getEventType')->willReturn($type);
        $event->method('getPayload')->willReturn($payload);
        $event->method('getMetadata')->willReturn([]);
        $event->method('getOccurredAt')->willReturn(new DateTimeImmutable());

        return $event;
    }
}
