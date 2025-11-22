<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories\Infrastructure;

use App\Models\Infrastructure\EventSnapshot;
use App\Repositories\Infrastructure\EloquentSnapshotRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SnapshotRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private EloquentSnapshotRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new EloquentSnapshotRepository();
    }

    /** @test */
    public function it_saves_snapshot(): void
    {
        $state = ['balance' => 1000, 'status' => 'active'];

        $this->repository->save('agg-123', 10, $state);

        $this->assertDatabaseHas('event_snapshots', [
            'aggregate_id' => 'agg-123',
            'version' => 10,
        ]);
    }

    /** @test */
    public function it_gets_latest_snapshot(): void
    {
        EventSnapshot::factory()->forAggregate('agg-123')->atVersion(5)->create();
        EventSnapshot::factory()->forAggregate('agg-123')->atVersion(10)->create();
        EventSnapshot::factory()->forAggregate('agg-456')->atVersion(3)->create();

        $snapshot = $this->repository->getLatest('agg-123');

        $this->assertNotNull($snapshot);
        $this->assertEquals(10, $snapshot->getVersion());
    }

    /** @test */
    public function it_returns_null_when_no_snapshot_exists(): void
    {
        $snapshot = $this->repository->getLatest('agg-999');

        $this->assertNull($snapshot);
    }

    /** @test */
    public function it_gets_snapshot_at_version(): void
    {
        EventSnapshot::factory()->forAggregate('agg-123')->atVersion(5)->create();
        EventSnapshot::factory()->forAggregate('agg-123')->atVersion(10)->create();
        EventSnapshot::factory()->forAggregate('agg-123')->atVersion(15)->create();

        $snapshot = $this->repository->getAtVersion('agg-123', 12);

        $this->assertNotNull($snapshot);
        $this->assertEquals(10, $snapshot->getVersion());
    }

    /** @test */
    public function it_deletes_old_snapshots(): void
    {
        $old = now()->subDays(100);
        $recent = now()->subDays(10);

        EventSnapshot::factory()->create(['created_at' => $old]);
        EventSnapshot::factory()->create(['created_at' => $recent]);

        $deleted = $this->repository->deleteOlderThan(
            \DateTimeImmutable::createFromMutable(now()->subDays(30))
        );

        $this->assertEquals(1, $deleted);
        $this->assertDatabaseCount('event_snapshots', 1);
    }

    /** @test */
    public function it_checks_if_snapshot_exists(): void
    {
        $this->assertFalse($this->repository->exists('agg-999'));

        EventSnapshot::factory()->forAggregate('agg-123')->create();

        $this->assertTrue($this->repository->exists('agg-123'));
    }
}
