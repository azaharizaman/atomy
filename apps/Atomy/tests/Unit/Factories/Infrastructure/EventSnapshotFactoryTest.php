<?php

declare(strict_types=1);

namespace Tests\Unit\Factories\Infrastructure;

use App\Models\Infrastructure\EventSnapshot;
use Database\Factories\Infrastructure\EventSnapshotFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class EventSnapshotFactoryTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_creates_snapshot_with_default_attributes(): void
    {
        $snapshot = EventSnapshot::factory()->make();

        $this->assertNotNull($snapshot->aggregate_id);
        $this->assertEquals(10, $snapshot->version);
        $this->assertIsArray($snapshot->state);
        $this->assertNotNull($snapshot->checksum);
    }

    /** @test */
    public function it_sets_aggregate_id_with_for_aggregate_state(): void
    {
        $snapshot = EventSnapshot::factory()->forAggregate('agg-789')->make();

        $this->assertEquals('agg-789', $snapshot->aggregate_id);
    }

    /** @test */
    public function it_sets_version_with_at_version_state(): void
    {
        $snapshot = EventSnapshot::factory()->atVersion(25)->make();

        $this->assertEquals(25, $snapshot->version);
    }

    /** @test */
    public function it_sets_state_with_with_state_method(): void
    {
        $state = ['balance' => 5000, 'currency' => 'USD'];
        $snapshot = EventSnapshot::factory()->withState($state)->make();

        $this->assertEquals($state, $snapshot->state);
        $this->assertEquals(hash('sha256', json_encode($state)), $snapshot->checksum);
    }

    /** @test */
    public function it_chains_state_methods(): void
    {
        $state = ['total' => 9999];
        $snapshot = EventSnapshot::factory()
            ->forAggregate('agg-abc')
            ->atVersion(50)
            ->withState($state)
            ->make();

        $this->assertEquals('agg-abc', $snapshot->aggregate_id);
        $this->assertEquals(50, $snapshot->version);
        $this->assertEquals($state, $snapshot->state);
    }

    /** @test */
    public function it_returns_new_factory_instance_for_chaining(): void
    {
        $factory1 = EventSnapshot::factory();
        $factory2 = $factory1->forAggregate('agg-123');

        $this->assertNotSame($factory1, $factory2);
        $this->assertInstanceOf(EventSnapshotFactory::class, $factory2);
    }
}
