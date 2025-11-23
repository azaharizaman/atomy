<?php

declare(strict_types=1);

namespace Nexus\EventStream\Tests\Unit\Services;

use Nexus\EventStream\Contracts\EventInterface;
use Nexus\EventStream\Contracts\EventQueryInterface;
use Nexus\EventStream\Contracts\ProjectorInterface;
use Nexus\EventStream\Exceptions\ProjectionLockedException;
use Nexus\EventStream\Services\DefaultProjectionManager;
use Nexus\EventStream\Services\InMemoryProjectionLock;
use Nexus\EventStream\Services\InMemoryProjectionStateRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

#[CoversClass(DefaultProjectionManager::class)]
#[CoversClass(ProjectionLockedException::class)]
final class DefaultProjectionManagerTest extends TestCase
{
    private EventQueryInterface $eventQuery;
    private InMemoryProjectionLock $lock;
    private InMemoryProjectionStateRepository $stateRepository;
    private DefaultProjectionManager $manager;

    protected function setUp(): void
    {
        $this->eventQuery = $this->createMock(EventQueryInterface::class);
        $this->lock = new InMemoryProjectionLock();
        $this->stateRepository = new InMemoryProjectionStateRepository();
        $this->manager = new DefaultProjectionManager(
            $this->eventQuery,
            $this->lock,
            $this->stateRepository,
            new NullLogger()
        );
    }

    protected function tearDown(): void
    {
        // Clean up locks and state between tests
        $this->lock->clearAll();
        $this->stateRepository->clearAll();
    }

    #[Test]
    public function it_implements_projection_manager_interface(): void
    {
        $this->assertInstanceOf(
            \Nexus\EventStream\Contracts\ProjectionManagerInterface::class,
            $this->manager
        );
    }

    #[Test]
    public function it_rebuilds_projection_from_scratch(): void
    {
        $projector = $this->createMockProjector('test-projector', ['EventA', 'EventB']);
        $events = $this->createMockEvents(5);

        $this->setupEventQueryExpectation($events, ['EventA', 'EventB'], null);

        $projector->expects($this->once())->method('reset');
        $projector->expects($this->exactly(5))->method('project');

        $result = $this->manager->rebuild($projector, 10);

        $this->assertSame(5, $result['processed']);
        $this->assertGreaterThan(0, $result['duration']);
        $this->assertSame('event-1', $result['from_event']);
        $this->assertSame('event-5', $result['to_event']);
    }

    #[Test]
    public function it_releases_lock_after_rebuild(): void
    {
        $projector = $this->createMockProjector('test-projector', ['EventA']);
        $this->setupEventQueryExpectation([], ['EventA'], null);

        $this->manager->rebuild($projector);

        $this->assertFalse($this->lock->isLocked('test-projector'));
    }

    #[Test]
    public function it_throws_when_projection_is_locked(): void
    {
        $projector = $this->createMockProjector('test-projector', []);
        $this->lock->acquire('test-projector');

        $this->expectException(ProjectionLockedException::class);
        $this->expectExceptionMessage('already locked');

        $this->manager->rebuild($projector);
    }

    #[Test]
    public function it_releases_lock_even_on_failure(): void
    {
        $projector = $this->createMockProjector('test-projector', []);
        $projector->method('reset')->willThrowException(new \RuntimeException('Reset failed'));

        try {
            $this->manager->rebuild($projector);
        } catch (\RuntimeException) {
            // Expected
        }

        $this->assertFalse($this->lock->isLocked('test-projector'));
    }

    #[Test]
    public function it_resumes_from_checkpoint(): void
    {
        $projector = $this->createMockProjector('test-projector', ['EventA']);

        // Set existing checkpoint
        $this->stateRepository->saveState('test-projector', 'event-3', new \DateTimeImmutable());

        $events = $this->createMockEvents(2, 4); // Events 4-5

        $this->setupEventQueryExpectation($events, ['EventA'], 'event-3');

        $projector->expects($this->never())->method('reset'); // Should NOT reset
        $projector->expects($this->exactly(2))->method('project');

        $result = $this->manager->resume($projector, 10);

        $this->assertSame(2, $result['processed']);
    }

    #[Test]
    public function it_rebuilds_if_no_checkpoint_on_resume(): void
    {
        $projector = $this->createMockProjector('test-projector', ['EventA']);
        $events = $this->createMockEvents(3);

        $this->setupEventQueryExpectation($events, ['EventA'], null);

        $projector->expects($this->once())->method('reset'); // Should reset

        $this->manager->resume($projector);
    }

    #[Test]
    public function it_saves_checkpoint_after_each_event(): void
    {
        $projector = $this->createMockProjector('test-projector', ['EventA']);
        $events = $this->createMockEvents(3);

        $this->setupEventQueryExpectation($events, ['EventA'], null);

        $this->manager->rebuild($projector);

        // Final checkpoint should be last event
        $this->assertSame('event-3', $this->stateRepository->getLastProcessedEventId('test-projector'));
    }

    #[Test]
    public function it_gets_projection_status(): void
    {
        $this->stateRepository->saveState(
            'test-projector',
            'event-123',
            new \DateTimeImmutable('2024-01-15 10:00:00')
        );

        $status = $this->manager->getStatus('test-projector');

        $this->assertFalse($status['is_locked']);
        $this->assertSame('event-123', $status['last_event_id']);
        $this->assertInstanceOf(\DateTimeImmutable::class, $status['last_processed_at']);
        $this->assertNull($status['lock_age_seconds']);
    }

    #[Test]
    public function it_gets_status_with_lock_info(): void
    {
        $this->lock->acquire('test-projector');
        sleep(1);

        $status = $this->manager->getStatus('test-projector');

        $this->assertTrue($status['is_locked']);
        $this->assertGreaterThanOrEqual(1, $status['lock_age_seconds']);
    }

    #[Test]
    public function it_force_resets_projection(): void
    {
        $this->lock->acquire('test-projector');
        $this->stateRepository->saveState('test-projector', 'event-123', new \DateTimeImmutable());

        $this->manager->forceReset('test-projector');

        $this->assertFalse($this->lock->isLocked('test-projector'));
        $this->assertFalse($this->stateRepository->hasState('test-projector'));
    }

    #[Test]
    public function it_processes_batches_correctly(): void
    {
        $projector = $this->createMockProjector('test-projector', ['EventA']);

        // First batch: 3 events
        $batch1 = $this->createMockEvents(3, 1);
        $result1 = $this->createMockCursorResult($batch1, 'cursor-1', true);

        // Second batch: 2 events
        $batch2 = $this->createMockEvents(2, 4);
        $result2 = $this->createMockCursorResult($batch2, null, false);

        $this->eventQuery
            ->method('whereIn')
            ->willReturnSelf();
        $this->eventQuery
            ->method('where')
            ->willReturnSelf();
        $this->eventQuery
            ->method('orderBy')
            ->willReturnSelf();
        $this->eventQuery
            ->method('withCursor')
            ->willReturnSelf();

        $this->eventQuery
            ->method('execute')
            ->willReturnOnConsecutiveCalls($result1, $result2);

        $projector->expects($this->exactly(5))->method('project'); // 3 + 2

        $result = $this->manager->rebuild($projector, 3);

        $this->assertSame(5, $result['processed']);
    }

    private function createMockProjector(string $name, array $handledTypes): ProjectorInterface
    {
        $projector = $this->createMock(ProjectorInterface::class);
        $projector->method('getName')->willReturn($name);
        $projector->method('getHandledEventTypes')->willReturn($handledTypes);
        return $projector;
    }

    private function createMockEvents(int $count, int $startIndex = 1): array
    {
        $events = [];
        for ($i = 0; $i < $count; $i++) {
            $index = $startIndex + $i;
            $event = $this->createMock(EventInterface::class);
            $event->method('getEventId')->willReturn("event-{$index}");
            $event->method('getVersion')->willReturn($index);
            $events[] = $event;
        }
        return $events;
    }

    private function setupEventQueryExpectation(
        array $events,
        array $eventTypes,
        ?string $afterEventId
    ): void {
        $result = $this->createMockCursorResult($events, null, false);

        $this->eventQuery
            ->method('whereIn')
            ->with('event_type', $eventTypes)
            ->willReturnSelf();

        if ($afterEventId !== null) {
            $this->eventQuery
                ->method('where')
                ->with('event_id', '>', $afterEventId)
                ->willReturnSelf();
        }

        $this->eventQuery
            ->method('orderBy')
            ->willReturnSelf();

        $this->eventQuery
            ->method('withCursor')
            ->willReturnSelf();

        $this->eventQuery
            ->method('execute')
            ->willReturn($result);
    }

    private function createMockCursorResult(
        array $events,
        ?string $nextCursor,
        bool $hasMore
    ): \Nexus\EventStream\Contracts\CursorResultInterface {
        $result = $this->createMock(\Nexus\EventStream\Contracts\CursorResultInterface::class);
        $result->method('getEvents')->willReturn($events);
        $result->method('getNextCursor')->willReturn($nextCursor);
        $result->method('hasMore')->willReturn($hasMore);
        return $result;
    }
}
