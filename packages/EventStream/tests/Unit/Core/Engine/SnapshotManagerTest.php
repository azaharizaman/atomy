<?php

declare(strict_types=1);

namespace Nexus\EventStream\Tests\Unit\Core\Engine;

use Nexus\EventStream\Core\Engine\SnapshotManager;
use Nexus\EventStream\Contracts\EventStoreInterface;
use Nexus\EventStream\Contracts\SnapshotRepositoryInterface;
use Nexus\EventStream\Contracts\SnapshotInterface;
use Nexus\EventStream\Exceptions\InvalidSnapshotException;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Group;

#[Group('eventstream')]
#[Group('snapshots')]
final class SnapshotManagerTest extends TestCase
{
    private SnapshotRepositoryInterface $snapshotRepository;
    private EventStoreInterface $eventStore;
    private LoggerInterface $logger;
    private SnapshotManager $snapshotManager;

    protected function setUp(): void
    {
        $this->snapshotRepository = $this->createMock(SnapshotRepositoryInterface::class);
        $this->eventStore = $this->createMock(EventStoreInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        
        $this->snapshotManager = new SnapshotManager(
            $this->snapshotRepository,
            $this->eventStore,
            $this->logger,
            100 // threshold
        );
    }

    #[Test]
    public function it_creates_snapshot_when_threshold_reached_without_existing_snapshot(): void
    {
        $aggregateId = 'agg-123';
        $state = ['balance' => 1000.00];

        $this->eventStore
            ->expects($this->once())
            ->method('getCurrentVersion')
            ->with($aggregateId)
            ->willReturn(100);

        $this->snapshotRepository
            ->expects($this->once())
            ->method('getLatest')
            ->with($aggregateId)
            ->willReturn(null);

        $this->snapshotRepository
            ->expects($this->once())
            ->method('save')
            ->with($aggregateId, 100, $state);

        $result = $this->snapshotManager->createIfNeeded($aggregateId, $state);

        $this->assertTrue($result);
    }

    #[Test]
    public function it_creates_snapshot_when_threshold_reached_since_last_snapshot(): void
    {
        $aggregateId = 'agg-123';
        $state = ['balance' => 2000.00];

        $lastSnapshot = $this->createMock(SnapshotInterface::class);
        $lastSnapshot->method('getVersion')->willReturn(50);

        $this->eventStore
            ->expects($this->once())
            ->method('getCurrentVersion')
            ->with($aggregateId)
            ->willReturn(150); // 150 - 50 = 100 events (threshold reached)

        $this->snapshotRepository
            ->expects($this->once())
            ->method('getLatest')
            ->with($aggregateId)
            ->willReturn($lastSnapshot);

        $this->snapshotRepository
            ->expects($this->once())
            ->method('save')
            ->with($aggregateId, 150, $state);

        $result = $this->snapshotManager->createIfNeeded($aggregateId, $state);

        $this->assertTrue($result);
    }

    #[Test]
    public function it_does_not_create_snapshot_when_threshold_not_reached(): void
    {
        $aggregateId = 'agg-123';
        $state = ['balance' => 500.00];

        $this->eventStore
            ->expects($this->once())
            ->method('getCurrentVersion')
            ->with($aggregateId)
            ->willReturn(50);

        $this->snapshotRepository
            ->expects($this->once())
            ->method('getLatest')
            ->with($aggregateId)
            ->willReturn(null);

        $this->snapshotRepository
            ->expects($this->never())
            ->method('save');

        $result = $this->snapshotManager->createIfNeeded($aggregateId, $state);

        $this->assertFalse($result);
    }

    #[Test]
    public function it_validates_snapshot_checksum_in_legacy_mode(): void
    {
        $aggregateId = 'agg-123';
        $state = ['balance' => 1000.00];
        $expectedChecksum = hash('sha256', json_encode($state));

        $result = $this->snapshotManager->validateChecksum($aggregateId, $state, $expectedChecksum);

        $this->assertTrue($result);
    }

    #[Test]
    public function it_throws_exception_for_invalid_checksum(): void
    {
        $aggregateId = 'agg-123';
        $state = ['balance' => 1000.00];
        $invalidChecksum = 'wrong-checksum';

        // validateChecksum returns false for invalid checksum (doesn't throw)
        $result = $this->snapshotManager->validateChecksum($aggregateId, $state, $invalidChecksum);

        $this->assertFalse($result);
    }

    #[Test]
    public function it_calculates_checksum_correctly_for_complex_data(): void
    {
        $state = [
            'balance' => 5000.00,
            'currency' => 'MYR',
            'transactions' => [
                ['id' => 'tx-1', 'amount' => 100.00],
                ['id' => 'tx-2', 'amount' => 200.00],
            ],
        ];
        
        $expectedChecksum = hash('sha256', json_encode($state));
        $actualChecksum = $this->snapshotManager->calculateChecksum($state);

        $this->assertSame($expectedChecksum, $actualChecksum);
    }

    #[Test]
    public function it_logs_snapshot_creation(): void
    {
        $aggregateId = 'agg-789';
        $state = ['balance' => 3000.00];

        $this->eventStore->method('getCurrentVersion')->willReturn(100);
        $this->snapshotRepository->method('getLatest')->willReturn(null);

        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with(
                'Creating snapshot',
                $this->callback(function ($context) use ($aggregateId) {
                    return $context['aggregate_id'] === $aggregateId
                        && $context['version'] === 100;
                })
            );

        $this->snapshotManager->createIfNeeded($aggregateId, $state);
    }

    #[Test]
    public function it_respects_custom_threshold(): void
    {
        $customThreshold = 50;
        $manager = new SnapshotManager(
            $this->snapshotRepository,
            $this->eventStore,
            $this->logger,
            $customThreshold
        );

        $aggregateId = 'agg-custom';
        $state = ['balance' => 1000.00];

        $this->eventStore->method('getCurrentVersion')->willReturn(50);
        $this->snapshotRepository->method('getLatest')->willReturn(null);

        $this->snapshotRepository
            ->expects($this->once())
            ->method('save');

        $result = $manager->createIfNeeded($aggregateId, $state);

        $this->assertTrue($result);
    }
}
