<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories\Infrastructure;

use App\Models\Infrastructure\EventStream;
use App\Repositories\Infrastructure\EloquentEventStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Nexus\EventStream\Exceptions\ConcurrencyException;
use Tests\TestCase;

final class EventStoreTest extends TestCase
{
    use RefreshDatabase;

    private EloquentEventStore $eventStore;

    protected function setUp(): void
    {
        parent::setUp();
        $this->eventStore = new EloquentEventStore();
    }

    /** @test */
    public function it_appends_event_to_stream(): void
    {
        $event = EventStream::factory()->forAggregate('agg-123')->withVersion(1)->make();

        $this->eventStore->append('agg-123', $event);

        $this->assertDatabaseHas('event_streams', [
            'aggregate_id' => 'agg-123',
            'version' => 1,
        ]);
    }

    /** @test */
    public function it_throws_concurrency_exception_on_version_conflict(): void
    {
        $event1 = EventStream::factory()->forAggregate('agg-123')->withVersion(1)->make();
        $this->eventStore->append('agg-123', $event1);

        $event2 = EventStream::factory()->forAggregate('agg-123')->withVersion(2)->make();

        $this->expectException(ConcurrencyException::class);
        $this->eventStore->append('agg-123', $event2, 0); // Expect version 0 but current is 1
    }

    /** @test */
    public function it_appends_batch_events_in_transaction(): void
    {
        $events = [
            EventStream::factory()->forAggregate('agg-123')->withVersion(1)->make(),
            EventStream::factory()->forAggregate('agg-123')->withVersion(2)->make(),
            EventStream::factory()->forAggregate('agg-123')->withVersion(3)->make(),
        ];

        $this->eventStore->appendBatch('agg-123', $events);

        $this->assertDatabaseCount('event_streams', 3);
        $this->assertEquals(3, $this->eventStore->getCurrentVersion('agg-123'));
    }

    /** @test */
    public function it_gets_current_version_of_stream(): void
    {
        $this->assertEquals(0, $this->eventStore->getCurrentVersion('agg-999'));

        EventStream::factory()->forAggregate('agg-123')->withVersion(5)->create();

        $this->assertEquals(5, $this->eventStore->getCurrentVersion('agg-123'));
    }

    /** @test */
    public function it_checks_if_stream_exists(): void
    {
        $this->assertFalse($this->eventStore->streamExists('agg-999'));

        EventStream::factory()->forAggregate('agg-123')->create();

        $this->assertTrue($this->eventStore->streamExists('agg-123'));
    }

    /** @test */
    public function it_reads_all_events_in_stream(): void
    {
        EventStream::factory()->forAggregate('agg-123')->withVersion(1)->create();
        EventStream::factory()->forAggregate('agg-123')->withVersion(2)->create();
        EventStream::factory()->forAggregate('agg-456')->withVersion(1)->create();

        $events = $this->eventStore->readStream('agg-123');

        $this->assertCount(2, $events);
        $this->assertEquals(1, $events[0]->getVersion());
        $this->assertEquals(2, $events[1]->getVersion());
    }

    /** @test */
    public function it_reads_stream_from_specific_version(): void
    {
        EventStream::factory()->forAggregate('agg-123')->withVersion(1)->create();
        EventStream::factory()->forAggregate('agg-123')->withVersion(2)->create();
        EventStream::factory()->forAggregate('agg-123')->withVersion(3)->create();

        $events = $this->eventStore->readStreamFromVersion('agg-123', 2);

        $this->assertCount(2, $events);
        $this->assertEquals(2, $events[0]->getVersion());
        $this->assertEquals(3, $events[1]->getVersion());
    }

    /** @test */
    public function it_reads_stream_within_version_range(): void
    {
        EventStream::factory()->forAggregate('agg-123')->withVersion(1)->create();
        EventStream::factory()->forAggregate('agg-123')->withVersion(2)->create();
        EventStream::factory()->forAggregate('agg-123')->withVersion(3)->create();
        EventStream::factory()->forAggregate('agg-123')->withVersion(4)->create();

        $events = $this->eventStore->readStreamFromVersion('agg-123', 2, 3);

        $this->assertCount(2, $events);
        $this->assertEquals(2, $events[0]->getVersion());
        $this->assertEquals(3, $events[1]->getVersion());
    }

    /** @test */
    public function it_reads_stream_until_timestamp(): void
    {
        $past = now()->subHours(2);
        $present = now();

        EventStream::factory()->forAggregate('agg-123')->create(['occurred_at' => $past]);
        EventStream::factory()->forAggregate('agg-123')->create(['occurred_at' => $present]);

        $events = $this->eventStore->readStreamUntil('agg-123', \DateTimeImmutable::createFromMutable($past->addMinutes(30)));

        $this->assertCount(1, $events);
    }

    /** @test */
    public function it_reads_events_by_type(): void
    {
        EventStream::factory()->ofType('TestEventA')->create();
        EventStream::factory()->ofType('TestEventA')->create();
        EventStream::factory()->ofType('TestEventB')->create();

        $events = $this->eventStore->readEventsByType('TestEventA');

        $this->assertCount(2, $events);
    }

    /** @test */
    public function it_reads_events_by_type_with_limit(): void
    {
        EventStream::factory()->ofType('TestEventA')->create();
        EventStream::factory()->ofType('TestEventA')->create();
        EventStream::factory()->ofType('TestEventA')->create();

        $events = $this->eventStore->readEventsByType('TestEventA', 2);

        $this->assertCount(2, $events);
    }

    /** @test */
    public function it_reads_events_by_type_and_date_range(): void
    {
        $start = now()->subDays(2);
        $end = now();

        EventStream::factory()->ofType('TestEventA')->create(['occurred_at' => $start->addHours(1)]);
        EventStream::factory()->ofType('TestEventA')->create(['occurred_at' => now()->subDays(3)]);

        $events = $this->eventStore->readEventsByTypeAndDateRange(
            'TestEventA',
            \DateTimeImmutable::createFromMutable($start),
            \DateTimeImmutable::createFromMutable($end)
        );

        $this->assertCount(1, $events);
    }
}
