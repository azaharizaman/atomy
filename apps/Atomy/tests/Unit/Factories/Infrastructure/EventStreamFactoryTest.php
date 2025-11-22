<?php

declare(strict_types=1);

namespace Tests\Unit\Factories\Infrastructure;

use App\Models\Infrastructure\EventStream;
use Database\Factories\Infrastructure\EventStreamFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class EventStreamFactoryTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_creates_event_with_default_attributes(): void
    {
        $event = EventStream::factory()->make();

        $this->assertNotNull($event->event_id);
        $this->assertNotNull($event->aggregate_id);
        $this->assertEquals(1, $event->version);
    }

    /** @test */
    public function it_sets_aggregate_id_with_for_aggregate_state(): void
    {
        $event = EventStream::factory()->forAggregate('agg-123')->make();

        $this->assertEquals('agg-123', $event->aggregate_id);
    }

    /** @test */
    public function it_sets_version_with_with_version_state(): void
    {
        $event = EventStream::factory()->withVersion(5)->make();

        $this->assertEquals(5, $event->version);
    }

    /** @test */
    public function it_marks_event_as_published(): void
    {
        $event = EventStream::factory()->published()->make();

        $this->assertNotNull($event->correlation_id);
    }

    /** @test */
    public function it_marks_event_as_pending(): void
    {
        $event = EventStream::factory()->pending()->make();

        $this->assertNull($event->correlation_id);
    }

    /** @test */
    public function it_sets_event_type_with_of_type_state(): void
    {
        $event = EventStream::factory()->ofType('CustomEvent')->make();

        $this->assertEquals('CustomEvent', $event->event_type);
    }

    /** @test */
    public function it_sets_payload_with_with_payload_state(): void
    {
        $payload = ['amount' => 1000, 'currency' => 'MYR'];
        $event = EventStream::factory()->withPayload($payload)->make();

        $this->assertEquals($payload, $event->payload);
    }

    /** @test */
    public function it_chains_state_methods(): void
    {
        $event = EventStream::factory()
            ->forAggregate('agg-456')
            ->withVersion(3)
            ->published()
            ->ofType('TestEvent')
            ->make();

        $this->assertEquals('agg-456', $event->aggregate_id);
        $this->assertEquals(3, $event->version);
        $this->assertNotNull($event->correlation_id);
        $this->assertEquals('TestEvent', $event->event_type);
    }

    /** @test */
    public function it_returns_new_factory_instance_for_chaining(): void
    {
        $factory1 = EventStream::factory();
        $factory2 = $factory1->forAggregate('agg-123');

        $this->assertNotSame($factory1, $factory2);
        $this->assertInstanceOf(EventStreamFactory::class, $factory2);
    }
}
