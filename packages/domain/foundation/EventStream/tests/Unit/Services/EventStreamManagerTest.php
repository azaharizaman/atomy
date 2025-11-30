<?php

declare(strict_types=1);

namespace Nexus\EventStream\Tests\Unit\Services;

use Nexus\EventStream\Services\EventStreamManager;
use Nexus\EventStream\Contracts\EventStoreInterface;
use Nexus\EventStream\Contracts\StreamReaderInterface;
use Nexus\EventStream\Contracts\SnapshotRepositoryInterface;
use Nexus\EventStream\Contracts\ProjectorInterface;
use Nexus\EventStream\Contracts\EventInterface;
use Nexus\EventStream\Core\Engine\ProjectionEngine;
use Nexus\EventStream\Core\Engine\SnapshotManager;
use Nexus\EventStream\ValueObjects\AggregateId;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Group;

#[Group('eventstream')]
#[Group('services')]
final class EventStreamManagerTest extends TestCase
{
    private EventStoreInterface $eventStore;
    private StreamReaderInterface $streamReader;
    private SnapshotRepositoryInterface $snapshotRepository;
    private ProjectionEngine $projectionEngine;
    private SnapshotManager $snapshotManager;
    private LoggerInterface $logger;
    private EventStreamManager $manager;

    protected function setUp(): void
    {
        $this->eventStore = $this->createMock(EventStoreInterface::class);
        $this->streamReader = $this->createMock(StreamReaderInterface::class);
        $this->snapshotRepository = $this->createMock(SnapshotRepositoryInterface::class);
        $this->projectionEngine = $this->createMock(ProjectionEngine::class);
        $this->snapshotManager = $this->createMock(SnapshotManager::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->manager = new EventStreamManager(
            $this->eventStore,
            $this->streamReader,
            $this->snapshotRepository,
            $this->projectionEngine,
            $this->snapshotManager,
            $this->logger
        );
    }

    #[Test]
    public function it_retrieves_state_from_snapshot_when_available(): void
    {
        $aggregateId = AggregateId::fromString('aggregate-123');
        $targetDate = new \DateTimeImmutable('2024-01-15');
        
        $snapshotState = ['balance' => 5000, 'version' => 100, 'created_at' => new \DateTimeImmutable('2024-01-10')];
        
        $this->snapshotRepository
            ->expects($this->once())
            ->method('getLatestSnapshotBefore')
            ->with((string) $aggregateId, $targetDate)
            ->willReturn($snapshotState);

        $this->streamReader
            ->expects($this->once())
            ->method('readStreamFromDate')
            ->with((string) $aggregateId, $snapshotState['created_at'])
            ->willReturn([]);

        $result = $this->manager->getStateAt($aggregateId, $targetDate);
        
        // Manager adds events_count to snapshot state
        $expectedState = $snapshotState;
        $expectedState['events_count'] = 0;
        
        $this->assertSame($expectedState, $result);
    }

    #[Test]
    public function it_rebuilds_state_from_events_when_no_snapshot_exists(): void
    {
        $aggregateId = AggregateId::fromString('aggregate-456');
        $targetDate = new \DateTimeImmutable('2024-01-15');
        
        $this->snapshotRepository
            ->method('getSnapshotAt')
            ->willReturn(null);

        $events = [
            $this->createMockEvent('AccountCredited', ['amount' => 1000]),
            $this->createMockEvent('AccountDebited', ['amount' => 500]),
        ];

        $this->streamReader
            ->expects($this->once())
            ->method('readStreamUpToDate')
            ->with((string) $aggregateId, $targetDate)
            ->willReturn($events);

        $result = $this->manager->getStateAt($aggregateId, $targetDate);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('events_count', $result);
        $this->assertEquals(2, $result['events_count']);
    }

    #[Test]
    public function it_applies_events_after_snapshot(): void
    {
        $aggregateId = AggregateId::fromString('aggregate-789');
        $targetDate = new \DateTimeImmutable('2024-01-15');
        
        $snapshotState = ['balance' => 5000, 'version' => 100];
        $snapshotDate = new \DateTimeImmutable('2024-01-10');

        $this->snapshotRepository
            ->method('getSnapshotAt')
            ->willReturn($snapshotState);

        $eventsAfterSnapshot = [
            $this->createMockEvent('AccountCredited', ['amount' => 1000]),
        ];

        $this->streamReader
            ->method('readStreamFromDate')
            ->with((string) $aggregateId, $snapshotDate)
            ->willReturn($eventsAfterSnapshot);

        $result = $this->manager->getStateAt($aggregateId, $targetDate);
        
        $this->assertIsArray($result);
    }

    #[Test]
    public function it_rebuilds_projection_using_projection_engine(): void
    {
        $streamId = 'account-ledger';
        $projector = $this->createMock(ProjectorInterface::class);
        $projector->method('getName')->willReturn('LedgerProjector');

        $this->projectionEngine
            ->expects($this->once())
            ->method('rebuild')
            ->with($streamId, $projector);

        $this->logger
            ->expects($this->atLeastOnce())
            ->method('info')
            ->with($this->stringContains('Rebuilding projection'));

        $this->manager->rebuildProjection($streamId, $projector);
    }

    #[Test]
    public function it_runs_projection_from_checkpoint(): void
    {
        $streamId = 'customer-stream';
        $projector = $this->createMock(ProjectorInterface::class);
        $projector->method('getName')->willReturn('CustomerProjector');

        $this->projectionEngine
            ->expects($this->once())
            ->method('run')
            ->with($streamId, $projector);

        $this->manager->runProjection($streamId, $projector);
    }

    #[Test]
    public function it_resumes_projection_from_specific_event(): void
    {
        $streamId = 'inventory-stream';
        $lastEventId = 'event-500';
        $projector = $this->createMock(ProjectorInterface::class);
        $projector->method('getName')->willReturn('InventoryProjector');

        $this->projectionEngine
            ->expects($this->once())
            ->method('resume')
            ->with($streamId, $projector, $lastEventId);

        $this->manager->resumeProjection($streamId, $projector, $lastEventId);
    }

    #[Test]
    public function it_provides_stream_health_metrics(): void
    {
        $aggregateId = AggregateId::fromString('aggregate-health');
        
        $events = array_fill(0, 250, $this->createMockEvent('EventType', []));

        $this->streamReader
            ->method('readStream')
            ->with((string) $aggregateId)
            ->willReturn($events);

        $this->snapshotRepository
            ->method('getLatestSnapshot')
            ->with((string) $aggregateId)
            ->willReturn(['version' => 200]);

        $health = $this->manager->getStreamHealth($aggregateId);
        
        $this->assertIsArray($health);
        $this->assertArrayHasKey('total_events', $health);
        $this->assertArrayHasKey('has_snapshot', $health);
        $this->assertArrayHasKey('events_since_snapshot', $health);
        $this->assertEquals(250, $health['total_events']);
        $this->assertTrue($health['has_snapshot']);
        $this->assertEquals(50, $health['events_since_snapshot']);
    }

    #[Test]
    public function it_reports_unhealthy_stream_without_snapshot(): void
    {
        $aggregateId = AggregateId::fromString('aggregate-no-snapshot');
        
        $events = array_fill(0, 500, $this->createMockEvent('EventType', []));

        $this->streamReader
            ->method('readStream')
            ->willReturn($events);

        $this->snapshotRepository
            ->method('getLatestSnapshot')
            ->willReturn(null);

        $health = $this->manager->getStreamHealth($aggregateId);
        
        $this->assertFalse($health['has_snapshot']);
        $this->assertEquals(500, $health['events_since_snapshot']);
        $this->assertEquals('needs_snapshot', $health['recommendation']);
    }

    #[Test]
    public function it_creates_snapshot_when_threshold_reached(): void
    {
        $aggregateId = AggregateId::fromString('aggregate-snapshot');
        
        $events = array_fill(0, 150, $this->createMockEvent('EventType', []));

        $this->streamReader
            ->method('readStream')
            ->willReturn($events);

        $this->snapshotManager
            ->expects($this->once())
            ->method('createIfNeeded')
            ->with((string) $aggregateId, ['events_count' => 150]);

        $this->manager->maintainStream($aggregateId);
    }

    #[Test]
    public function it_handles_concurrent_writes_gracefully(): void
    {
        $aggregateId = AggregateId::fromString('aggregate-concurrent');
        
        // Simulate version conflict scenario
        $this->eventStore
            ->method('append')
            ->willThrowException(
                new \Nexus\EventStream\Exceptions\ConcurrencyException(
                    (string) $aggregateId,
                    100,
                    101
                )
            );

        $this->logger
            ->expects($this->once())
            ->method('warning')
            ->with(
                $this->stringContains('Concurrency conflict'),
                $this->callback(function ($context) {
                    return isset($context['aggregate_id'])
                        && isset($context['expected_version'])
                        && isset($context['actual_version']);
                })
            );

        $this->expectException(\Nexus\EventStream\Exceptions\ConcurrencyException::class);
        
        $event = $this->createMock(EventInterface::class);
        $this->manager->appendEvent((string) $aggregateId, $event, 100);
    }

    private function createMockEvent(string $eventType, array $payload): EventInterface
    {
        $event = $this->createMock(EventInterface::class);
        $event->method('getEventType')->willReturn($eventType);
        $event->method('getPayload')->willReturn($payload);
        $event->method('getEventId')->willReturn('event-' . bin2hex(random_bytes(8)));
        
        return $event;
    }
}
