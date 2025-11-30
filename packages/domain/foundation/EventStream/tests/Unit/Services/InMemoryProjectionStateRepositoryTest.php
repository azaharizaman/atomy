<?php

declare(strict_types=1);

namespace Nexus\EventStream\Tests\Unit\Services;

use Nexus\EventStream\Services\InMemoryProjectionStateRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(InMemoryProjectionStateRepository::class)]
final class InMemoryProjectionStateRepositoryTest extends TestCase
{
    private InMemoryProjectionStateRepository $repository;

    protected function setUp(): void
    {
        $this->repository = new InMemoryProjectionStateRepository();
    }

    #[Test]
    public function it_implements_projection_state_repository_interface(): void
    {
        $this->assertInstanceOf(
            \Nexus\EventStream\Contracts\ProjectionStateRepositoryInterface::class,
            $this->repository
        );
    }

    #[Test]
    public function it_returns_null_for_nonexistent_state(): void
    {
        $this->assertNull($this->repository->getLastProcessedEventId('test-projector'));
        $this->assertNull($this->repository->getLastProcessedAt('test-projector'));
        $this->assertFalse($this->repository->hasState('test-projector'));
    }

    #[Test]
    public function it_saves_state(): void
    {
        $eventId = '01HXZ123';
        $processedAt = new \DateTimeImmutable('2024-01-15 10:00:00');

        $this->repository->saveState('test-projector', $eventId, $processedAt);

        $this->assertSame($eventId, $this->repository->getLastProcessedEventId('test-projector'));
        $this->assertEquals($processedAt, $this->repository->getLastProcessedAt('test-projector'));
        $this->assertTrue($this->repository->hasState('test-projector'));
    }

    #[Test]
    public function it_updates_state(): void
    {
        $this->repository->saveState(
            'test-projector',
            '01HXZ111',
            new \DateTimeImmutable('2024-01-15 10:00:00')
        );

        $newEventId = '01HXZ222';
        $newProcessedAt = new \DateTimeImmutable('2024-01-15 11:00:00');

        $this->repository->saveState('test-projector', $newEventId, $newProcessedAt);

        $this->assertSame($newEventId, $this->repository->getLastProcessedEventId('test-projector'));
        $this->assertEquals($newProcessedAt, $this->repository->getLastProcessedAt('test-projector'));
    }

    #[Test]
    public function it_resets_state(): void
    {
        $this->repository->saveState(
            'test-projector',
            '01HXZ123',
            new \DateTimeImmutable()
        );

        $this->repository->resetState('test-projector');

        $this->assertNull($this->repository->getLastProcessedEventId('test-projector'));
        $this->assertNull($this->repository->getLastProcessedAt('test-projector'));
        $this->assertFalse($this->repository->hasState('test-projector'));
    }

    #[Test]
    public function it_handles_multiple_projectors_independently(): void
    {
        $this->repository->saveState(
            'projector-a',
            '01HXZ111',
            new \DateTimeImmutable('2024-01-15 10:00:00')
        );

        $this->repository->saveState(
            'projector-b',
            '01HXZ222',
            new \DateTimeImmutable('2024-01-15 11:00:00')
        );

        $this->assertSame('01HXZ111', $this->repository->getLastProcessedEventId('projector-a'));
        $this->assertSame('01HXZ222', $this->repository->getLastProcessedEventId('projector-b'));

        $this->repository->resetState('projector-a');

        $this->assertFalse($this->repository->hasState('projector-a'));
        $this->assertTrue($this->repository->hasState('projector-b'));
    }

    #[Test]
    public function it_clears_all_states(): void
    {
        $this->repository->saveState('projector-a', '01HXZ111', new \DateTimeImmutable());
        $this->repository->saveState('projector-b', '01HXZ222', new \DateTimeImmutable());

        $this->repository->clearAll();

        $this->assertFalse($this->repository->hasState('projector-a'));
        $this->assertFalse($this->repository->hasState('projector-b'));
    }

    #[Test]
    public function it_preserves_timestamp_timezone(): void
    {
        $timestamp = new \DateTimeImmutable('2024-01-15 10:00:00', new \DateTimeZone('Asia/Kuala_Lumpur'));

        $this->repository->saveState('test-projector', '01HXZ123', $timestamp);

        $retrieved = $this->repository->getLastProcessedAt('test-projector');

        $this->assertEquals($timestamp, $retrieved);
        $this->assertSame('Asia/Kuala_Lumpur', $retrieved->getTimezone()->getName());
    }
}
