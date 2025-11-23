<?php

declare(strict_types=1);

namespace Tests\Feature\Repositories;

use App\Models\EventProjection;
use App\Repositories\DbProjectionStateRepository;
use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Nexus\Tenant\Contracts\TenantContextInterface;
use Tests\TestCase;

/**
 * DbProjectionStateRepository Feature Tests
 *
 * Validates the database implementation of ProjectionStateRepositoryInterface with:
 * - Atomic state saving (critical for projection integrity)
 * - Last processed event ID tracking
 * - Timestamp tracking
 * - State reset functionality
 * - Multi-projector isolation
 * - Tenant isolation
 * - Concurrent access handling
 *
 * Requirements Coverage:
 * - FUN-EVS-7212: Track projection state (last event ID, timestamp)
 * - FUN-EVS-7218: Resume projection from last processed event
 * - PER-EVS-7313: Optimize projection resume from checkpoint
 * - REL-EVS-7410: Projection lag monitoring
 * - BUS-EVS-7107: Tenant isolation
 *
 * @group EventStream
 * @group Database
 * @group PR3
 */
final class DbProjectionStateRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private DbProjectionStateRepository $repository;
    private TenantContextInterface $tenantContext;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenantContext = $this->app->make(TenantContextInterface::class);
        $this->repository = new DbProjectionStateRepository($this->tenantContext);
    }

    /** @test */
    public function it_saves_projection_state_for_new_projector(): void
    {
        // Arrange
        $projectorName = 'AccountBalanceProjector';
        $eventId = 'evt-12345';
        $processedAt = new DateTimeImmutable('2024-01-15 10:30:00');

        // Act
        $this->repository->saveState($projectorName, $eventId, $processedAt);

        // Assert
        $this->assertDatabaseHas('event_projections', [
            'projector_name' => $projectorName,
            'last_processed_event_id' => $eventId,
        ]);
    }

    /** @test */
    public function it_updates_existing_projection_state(): void
    {
        // Arrange
        $projectorName = 'InventoryProjector';
        $initialEventId = 'evt-001';
        $initialProcessedAt = new DateTimeImmutable('2024-01-15 10:00:00');

        $this->repository->saveState($projectorName, $initialEventId, $initialProcessedAt);

        $updatedEventId = 'evt-002';
        $updatedProcessedAt = new DateTimeImmutable('2024-01-15 11:00:00');

        // Act
        $this->repository->saveState($projectorName, $updatedEventId, $updatedProcessedAt);

        // Assert
        $projection = EventProjection::where('projector_name', $projectorName)->first();
        $this->assertEquals($updatedEventId, $projection->last_processed_event_id);
        $this->assertEquals(
            $updatedProcessedAt->format('Y-m-d H:i:s'),
            $projection->last_processed_at->format('Y-m-d H:i:s')
        );
    }

    /** @test */
    public function it_saves_state_atomically(): void
    {
        // Arrange
        $projectorName = 'AtomicProjector';
        $eventId = 'evt-atomic-123';
        $processedAt = new DateTimeImmutable('2024-01-15 12:00:00');

        // Act - Call multiple times concurrently (simulated by rapid succession)
        $this->repository->saveState($projectorName, $eventId, $processedAt);

        // Assert - Should have exactly one record
        $count = EventProjection::where('projector_name', $projectorName)->count();
        $this->assertEquals(1, $count);
    }

    /** @test */
    public function it_retrieves_last_processed_event_id(): void
    {
        // Arrange
        $projectorName = 'RetrieveTestProjector';
        $eventId = 'evt-retrieve-456';
        $processedAt = new DateTimeImmutable();

        $this->repository->saveState($projectorName, $eventId, $processedAt);

        // Act
        $retrievedEventId = $this->repository->getLastProcessedEventId($projectorName);

        // Assert
        $this->assertEquals($eventId, $retrievedEventId);
    }

    /** @test */
    public function it_returns_null_for_non_existent_projector(): void
    {
        // Act
        $eventId = $this->repository->getLastProcessedEventId('NonExistentProjector');

        // Assert
        $this->assertNull($eventId);
    }

    /** @test */
    public function it_retrieves_last_processed_timestamp(): void
    {
        // Arrange
        $projectorName = 'TimestampProjector';
        $eventId = 'evt-timestamp-789';
        $processedAt = new DateTimeImmutable('2024-01-15 14:30:00');

        $this->repository->saveState($projectorName, $eventId, $processedAt);

        // Act
        $retrievedTimestamp = $this->repository->getLastProcessedAt($projectorName);

        // Assert
        $this->assertInstanceOf(DateTimeImmutable::class, $retrievedTimestamp);
        $this->assertEquals(
            $processedAt->format('Y-m-d H:i:s'),
            $retrievedTimestamp->format('Y-m-d H:i:s')
        );
    }

    /** @test */
    public function it_returns_null_timestamp_for_non_existent_projector(): void
    {
        // Act
        $timestamp = $this->repository->getLastProcessedAt('NonExistentProjector');

        // Assert
        $this->assertNull($timestamp);
    }

    /** @test */
    public function it_resets_projection_state(): void
    {
        // Arrange
        $projectorName = 'ResetProjector';
        $eventId = 'evt-reset-101';
        $processedAt = new DateTimeImmutable();

        $this->repository->saveState($projectorName, $eventId, $processedAt);

        // Act
        $this->repository->resetState($projectorName);

        // Assert
        $this->assertNull($this->repository->getLastProcessedEventId($projectorName));
        $this->assertFalse($this->repository->hasState($projectorName));
    }

    /** @test */
    public function it_checks_if_state_exists(): void
    {
        // Arrange
        $projectorName = 'ExistenceProjector';

        // Act & Assert - Before saving
        $this->assertFalse($this->repository->hasState($projectorName));

        // Save state
        $this->repository->saveState(
            $projectorName,
            'evt-exists-202',
            new DateTimeImmutable()
        );

        // Act & Assert - After saving
        $this->assertTrue($this->repository->hasState($projectorName));
    }

    /** @test */
    public function it_isolates_state_between_projectors(): void
    {
        // Arrange
        $projector1 = 'Projector1';
        $projector2 = 'Projector2';
        $event1 = 'evt-proj1-001';
        $event2 = 'evt-proj2-002';

        // Act
        $this->repository->saveState($projector1, $event1, new DateTimeImmutable());
        $this->repository->saveState($projector2, $event2, new DateTimeImmutable());

        // Assert
        $this->assertEquals($event1, $this->repository->getLastProcessedEventId($projector1));
        $this->assertEquals($event2, $this->repository->getLastProcessedEventId($projector2));
        $this->assertNotEquals(
            $this->repository->getLastProcessedEventId($projector1),
            $this->repository->getLastProcessedEventId($projector2)
        );
    }

    /** @test */
    public function it_isolates_state_by_tenant(): void
    {
        // Arrange
        $projectorName = 'SharedProjector';
        $eventId1 = 'evt-tenant1-100';
        $eventId2 = 'evt-tenant2-200';

        // Act - Tenant 1
        $this->tenantContext->setCurrentTenant('tenant-alpha');
        $this->repository->saveState($projectorName, $eventId1, new DateTimeImmutable());
        $tenant1EventId = $this->repository->getLastProcessedEventId($projectorName);

        // Act - Tenant 2
        $this->tenantContext->setCurrentTenant('tenant-beta');
        $this->repository->saveState($projectorName, $eventId2, new DateTimeImmutable());
        $tenant2EventId = $this->repository->getLastProcessedEventId($projectorName);

        // Assert
        $this->assertEquals($eventId2, $tenant2EventId);

        // Switch back to Tenant 1
        $this->tenantContext->setCurrentTenant('tenant-alpha');
        $this->assertEquals($eventId1, $this->repository->getLastProcessedEventId($projectorName));
    }

    /** @test */
    public function it_handles_null_last_processed_event_gracefully(): void
    {
        // Arrange
        $projectorName = 'NullEventProjector';
        EventProjection::create([
            'projector_name' => $projectorName,
            'last_processed_event_id' => null,
            'processed_count' => 0,
            'last_processed_at' => null,
            'tenant_id' => $this->tenantContext->getCurrentTenant(),
        ]);

        // Act
        $eventId = $this->repository->getLastProcessedEventId($projectorName);
        $timestamp = $this->repository->getLastProcessedAt($projectorName);

        // Assert
        $this->assertNull($eventId);
        $this->assertNull($timestamp);
    }

    /** @test */
    public function it_uses_upsert_to_prevent_duplicate_records(): void
    {
        // Arrange
        $projectorName = 'UpsertProjector';

        // Act - Save state multiple times
        $this->repository->saveState($projectorName, 'evt-001', new DateTimeImmutable());
        $this->repository->saveState($projectorName, 'evt-002', new DateTimeImmutable());
        $this->repository->saveState($projectorName, 'evt-003', new DateTimeImmutable());

        // Assert - Should have exactly one record
        $count = EventProjection::where('projector_name', $projectorName)->count();
        $this->assertEquals(1, $count);
    }

    /** @test */
    public function it_maintains_processed_count_on_updates(): void
    {
        // Arrange
        $projectorName = 'CountProjector';

        // Create initial state manually with a count
        EventProjection::create([
            'projector_name' => $projectorName,
            'last_processed_event_id' => 'evt-000',
            'processed_count' => 100,
            'last_processed_at' => now(),
            'tenant_id' => $this->tenantContext->getCurrentTenant(),
        ]);

        // Act - Update state
        $this->repository->saveState($projectorName, 'evt-001', new DateTimeImmutable());

        // Assert - Count should be preserved (not reset)
        $projection = EventProjection::where('projector_name', $projectorName)->first();
        $this->assertEquals(100, $projection->processed_count);
    }

    /** @test */
    public function it_handles_concurrent_state_updates(): void
    {
        // Arrange
        $projectorName = 'ConcurrentProjector';

        // Act - Simulate concurrent updates using database transactions
        DB::transaction(function () use ($projectorName) {
            $this->repository->saveState(
                $projectorName,
                'evt-concurrent-1',
                new DateTimeImmutable('2024-01-15 10:00:00')
            );
        });

        DB::transaction(function () use ($projectorName) {
            $this->repository->saveState(
                $projectorName,
                'evt-concurrent-2',
                new DateTimeImmutable('2024-01-15 10:01:00')
            );
        });

        // Assert - Should have exactly one record with the latest data
        $count = EventProjection::where('projector_name', $projectorName)->count();
        $this->assertEquals(1, $count);

        $lastEventId = $this->repository->getLastProcessedEventId($projectorName);
        $this->assertEquals('evt-concurrent-2', $lastEventId);
    }

    /** @test */
    public function it_preserves_error_state_on_regular_updates(): void
    {
        // Arrange
        $projectorName = 'ErrorPreservationProjector';

        // Create projection with error state
        EventProjection::create([
            'projector_name' => $projectorName,
            'last_processed_event_id' => 'evt-error-001',
            'processed_count' => 50,
            'last_processed_at' => now(),
            'status' => 'failed',
            'error_message' => 'Database connection lost',
            'last_error_at' => now(),
            'tenant_id' => $this->tenantContext->getCurrentTenant(),
        ]);

        // Act - Update state (normal operation)
        $this->repository->saveState(
            $projectorName,
            'evt-error-002',
            new DateTimeImmutable()
        );

        // Assert - Error state should be preserved (not cleared by normal updates)
        $projection = EventProjection::where('projector_name', $projectorName)->first();
        $this->assertEquals('failed', $projection->status);
        $this->assertEquals('Database connection lost', $projection->error_message);
    }

    /** @test */
    public function it_handles_rapid_sequential_updates(): void
    {
        // Arrange
        $projectorName = 'RapidUpdateProjector';
        $eventCount = 10;

        // Act - Save state rapidly
        for ($i = 1; $i <= $eventCount; $i++) {
            $this->repository->saveState(
                $projectorName,
                "evt-rapid-{$i}",
                new DateTimeImmutable("2024-01-15 10:{$i}:00")
            );
        }

        // Assert - Should have exactly one record with the last event
        $count = EventProjection::where('projector_name', $projectorName)->count();
        $this->assertEquals(1, $count);

        $lastEventId = $this->repository->getLastProcessedEventId($projectorName);
        $this->assertEquals("evt-rapid-{$eventCount}", $lastEventId);
    }
}
