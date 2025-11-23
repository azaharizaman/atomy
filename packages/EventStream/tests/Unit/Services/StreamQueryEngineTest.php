<?php

declare(strict_types=1);

namespace Nexus\EventStream\Tests\Unit\Services;

use Nexus\EventStream\Contracts\CursorPaginatorInterface;
use Nexus\EventStream\Contracts\EventInterface;
use Nexus\EventStream\Contracts\EventStoreInterface;
use Nexus\EventStream\Services\StreamQueryEngine;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(StreamQueryEngine::class)]
final class StreamQueryEngineTest extends TestCase
{
    private EventStoreInterface $eventStore;
    private CursorPaginatorInterface $cursorPaginator;
    private StreamQueryEngine $queryEngine;

    protected function setUp(): void
    {
        $this->eventStore = $this->createMock(EventStoreInterface::class);
        $this->cursorPaginator = $this->createMock(CursorPaginatorInterface::class);
        $this->queryEngine = new StreamQueryEngine(
            $this->eventStore,
            $this->cursorPaginator
        );
    }

    #[Test]
    public function it_implements_event_query_interface(): void
    {
        $this->assertInstanceOf(
            \Nexus\EventStream\Contracts\EventQueryInterface::class,
            $this->queryEngine
        );
    }

    #[Test]
    public function where_returns_new_instance(): void
    {
        $newQuery = $this->queryEngine->where('aggregate_id', '=', '01HXZ123');

        $this->assertNotSame($this->queryEngine, $newQuery);
        $this->assertInstanceOf(StreamQueryEngine::class, $newQuery);
    }

    #[Test]
    public function where_in_returns_new_instance(): void
    {
        $newQuery = $this->queryEngine->whereIn('event_type', ['Created', 'Updated']);

        $this->assertNotSame($this->queryEngine, $newQuery);
        $this->assertInstanceOf(StreamQueryEngine::class, $newQuery);
    }

    #[Test]
    public function order_by_returns_new_instance(): void
    {
        $newQuery = $this->queryEngine->orderBy('occurred_at', 'desc');

        $this->assertNotSame($this->queryEngine, $newQuery);
        $this->assertInstanceOf(StreamQueryEngine::class, $newQuery);
    }

    #[Test]
    public function limit_returns_new_instance(): void
    {
        $newQuery = $this->queryEngine->limit(50);

        $this->assertNotSame($this->queryEngine, $newQuery);
        $this->assertInstanceOf(StreamQueryEngine::class, $newQuery);
    }

    #[Test]
    public function with_cursor_returns_new_instance(): void
    {
        $newQuery = $this->queryEngine->withCursor('base64-cursor', 50);

        $this->assertNotSame($this->queryEngine, $newQuery);
        $this->assertInstanceOf(StreamQueryEngine::class, $newQuery);
    }

    #[Test]
    public function order_by_rejects_invalid_direction(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Order direction must be 'asc' or 'desc'");

        $this->queryEngine->orderBy('sequence', 'invalid');
    }

    #[Test]
    public function order_by_accepts_uppercase_direction(): void
    {
        $newQuery = $this->queryEngine->orderBy('sequence', 'DESC');

        $this->assertInstanceOf(StreamQueryEngine::class, $newQuery);
    }

    #[Test]
    public function limit_rejects_zero(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Limit must be at least 1');

        $this->queryEngine->limit(0);
    }

    #[Test]
    public function limit_rejects_negative_values(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Limit must be at least 1');

        $this->queryEngine->limit(-10);
    }

    #[Test]
    public function execute_queries_event_store_with_filters(): void
    {
        $event1 = $this->createMockEvent('01HXZ111', 1);
        $event2 = $this->createMockEvent('01HXZ222', 2);

        $this->eventStore
            ->expects($this->once())
            ->method('query')
            ->with(
                ['aggregate_id' => ['operator' => '=', 'value' => '01HXZ123']],
                [],
                'sequence',
                'asc',
                101, // limit + 1 to check if more exist
                null
            )
            ->willReturn([$event1, $event2]);

        $result = $this->queryEngine
            ->where('aggregate_id', '=', '01HXZ123')
            ->execute();

        $this->assertCount(2, $result->getEvents());
        $this->assertFalse($result->hasMore());
    }

    #[Test]
    public function execute_queries_with_in_filters(): void
    {
        $this->eventStore
            ->expects($this->once())
            ->method('query')
            ->with(
                [],
                ['event_type' => ['Created', 'Updated']],
                'sequence',
                'asc',
                101,
                null
            )
            ->willReturn([]);

        $this->queryEngine
            ->whereIn('event_type', ['Created', 'Updated'])
            ->execute();
    }

    #[Test]
    public function execute_queries_with_custom_ordering(): void
    {
        $this->eventStore
            ->expects($this->once())
            ->method('query')
            ->with(
                [],
                [],
                'occurred_at',
                'desc',
                101,
                null
            )
            ->willReturn([]);

        $this->queryEngine
            ->orderBy('occurred_at', 'desc')
            ->execute();
    }

    #[Test]
    public function execute_queries_with_custom_limit(): void
    {
        $this->eventStore
            ->expects($this->once())
            ->method('query')
            ->with(
                [],
                [],
                'sequence',
                'asc',
                51, // 50 + 1
                null
            )
            ->willReturn([]);

        $this->queryEngine
            ->withCursor(null, 50)
            ->execute();
    }

    #[Test]
    public function execute_detects_more_results(): void
    {
        // Create 101 events (limit is 100 by default)
        $events = array_map(
            fn($i) => $this->createMockEvent("event-{$i}", $i),
            range(1, 101)
        );

        $this->eventStore
            ->method('query')
            ->willReturn($events);

        $this->cursorPaginator
            ->method('createCursor')
            ->willReturn('next-page-cursor');

        $result = $this->queryEngine->execute();

        $this->assertCount(100, $result->getEvents()); // Extra removed
        $this->assertTrue($result->hasMore());
        $this->assertSame('next-page-cursor', $result->getNextCursor());
    }

    #[Test]
    public function execute_generates_cursor_for_next_page(): void
    {
        $lastEvent = $this->createMockEvent('01HXZ999', 999);
        $events = array_map(
            fn($i) => $this->createMockEvent("event-{$i}", $i),
            range(1, 100)
        );
        $events[] = $lastEvent; // 101 total

        $this->eventStore
            ->method('query')
            ->willReturn($events);

        $this->cursorPaginator
            ->expects($this->once())
            ->method('createCursor')
            ->with('event-100', 100) // Last event before extra
            ->willReturn('next-cursor');

        $result = $this->queryEngine->execute();

        $this->assertSame('next-cursor', $result->getNextCursor());
    }

    #[Test]
    public function execute_parses_cursor_for_pagination(): void
    {
        $cursorData = ['event_id' => '01HXZ123', 'sequence' => 50];

        $this->cursorPaginator
            ->expects($this->once())
            ->method('parseCursor')
            ->with('my-cursor')
            ->willReturn($cursorData);

        $this->eventStore
            ->expects($this->once())
            ->method('query')
            ->with(
                [],
                [],
                'sequence',
                'asc',
                101,
                $cursorData
            )
            ->willReturn([]);

        $this->queryEngine
            ->withCursor('my-cursor', 100)
            ->execute();
    }

    #[Test]
    public function execute_returns_current_cursor(): void
    {
        $this->cursorPaginator
            ->method('parseCursor')
            ->willReturn(['event_id' => '01HXZ123', 'sequence' => 50]);

        $this->eventStore
            ->method('query')
            ->willReturn([]);

        $result = $this->queryEngine
            ->withCursor('my-cursor', 100)
            ->execute();

        $this->assertSame('my-cursor', $result->getCurrentCursor());
    }

    #[Test]
    public function count_queries_event_store(): void
    {
        $this->eventStore
            ->expects($this->once())
            ->method('count')
            ->with(
                [
                    'aggregate_id' => ['operator' => '=', 'value' => '01HXZ123'],
                ],
                ['event_type' => ['Created', 'Updated']]
            )
            ->willReturn(42);

        $count = $this->queryEngine
            ->where('aggregate_id', '=', '01HXZ123')
            ->whereIn('event_type', ['Created', 'Updated'])
            ->count();

        $this->assertSame(42, $count);
    }

    #[Test]
    public function fluent_interface_chains_multiple_conditions(): void
    {
        $this->eventStore
            ->expects($this->once())
            ->method('query')
            ->with(
                [
                    'aggregate_id' => ['operator' => '=', 'value' => '01HXZ123'],
                    'event_type' => ['operator' => '=', 'value' => 'AccountCredited'],
                ],
                ['stream_name' => ['account-1', 'account-2']],
                'occurred_at',
                'desc',
                26, // 25 + 1
                null
            )
            ->willReturn([]);

        $this->queryEngine
            ->where('aggregate_id', '=', '01HXZ123')
            ->where('event_type', '=', 'AccountCredited')
            ->whereIn('stream_name', ['account-1', 'account-2'])
            ->orderBy('occurred_at', 'desc')
            ->withCursor(null, 25)
            ->execute();
    }

    #[Test]
    public function with_cursor_null_clears_cursor(): void
    {
        $this->eventStore
            ->expects($this->once())
            ->method('query')
            ->with(
                [],
                [],
                'sequence',
                'asc',
                51,
                null // Cursor should be null
            )
            ->willReturn([]);

        $this->queryEngine
            ->withCursor('some-cursor', 50)
            ->withCursor(null, 50)
            ->execute();
    }

    #[Test]
    public function with_cursor_rejects_invalid_limit(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Limit must be at least 1');

        $this->queryEngine->withCursor('cursor', 0);
    }

    private function createMockEvent(string $id, int $version): EventInterface
    {
        $event = $this->createMock(EventInterface::class);
        $event->method('getEventId')->willReturn($id);
        $event->method('getVersion')->willReturn($version);
        return $event;
    }
}
