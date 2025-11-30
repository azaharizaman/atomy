<?php

declare(strict_types=1);

namespace Nexus\EventStream\Tests\Unit\Services;

use Nexus\EventStream\Contracts\EventInterface;
use Nexus\EventStream\Services\CursorResult;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(CursorResult::class)]
final class CursorResultTest extends TestCase
{
    #[Test]
    public function it_implements_cursor_result_interface(): void
    {
        $result = new CursorResult([], null, null, false);

        $this->assertInstanceOf(
            \Nexus\EventStream\Contracts\CursorResultInterface::class,
            $result
        );
    }

    #[Test]
    public function it_returns_events(): void
    {
        $event1 = $this->createMock(EventInterface::class);
        $event2 = $this->createMock(EventInterface::class);

        $result = new CursorResult([$event1, $event2], null, null, false);

        $this->assertSame([$event1, $event2], $result->getEvents());
    }

    #[Test]
    public function it_returns_next_cursor(): void
    {
        $cursor = 'base64-encoded-cursor';
        $result = new CursorResult([], $cursor, null, true);

        $this->assertSame($cursor, $result->getNextCursor());
    }

    #[Test]
    public function it_returns_null_when_no_more_results(): void
    {
        $result = new CursorResult([], null, null, false);

        $this->assertNull($result->getNextCursor());
        $this->assertFalse($result->hasMore());
    }

    #[Test]
    public function it_indicates_more_results_available(): void
    {
        $result = new CursorResult([], 'next-cursor', null, true);

        $this->assertTrue($result->hasMore());
    }

    #[Test]
    public function it_counts_events(): void
    {
        $events = [
            $this->createMock(EventInterface::class),
            $this->createMock(EventInterface::class),
            $this->createMock(EventInterface::class),
        ];

        $result = new CursorResult($events, null, null, false);

        $this->assertSame(3, $result->getCount());
    }

    #[Test]
    public function it_returns_empty_count_for_no_events(): void
    {
        $result = new CursorResult([], null, null, false);

        $this->assertSame(0, $result->getCount());
    }

    #[Test]
    public function it_returns_current_cursor(): void
    {
        $currentCursor = 'current-page-cursor';
        $result = new CursorResult([], null, $currentCursor, false);

        $this->assertSame($currentCursor, $result->getCurrentCursor());
    }

    #[Test]
    public function it_returns_null_for_first_page(): void
    {
        $result = new CursorResult([], null, null, false);

        $this->assertNull($result->getCurrentCursor());
    }

    #[Test]
    public function it_is_immutable(): void
    {
        $events = [
            $this->createMock(EventInterface::class),
        ];

        $result = new CursorResult($events, 'next', 'current', true);

        // Get events and try to modify
        $retrievedEvents = $result->getEvents();
        $retrievedEvents[] = $this->createMock(EventInterface::class);

        // Original result should be unchanged
        $this->assertCount(1, $result->getEvents());
    }
}
