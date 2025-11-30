<?php

declare(strict_types=1);

namespace Nexus\EventStream\Tests\Unit\ValueObjects;

use Nexus\EventStream\ValueObjects\EventId;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Group;

#[Group('eventstream')]
#[Group('value-objects')]
final class EventIdTest extends TestCase
{
    #[Test]
    public function it_generates_unique_event_ids(): void
    {
        $id1 = EventId::generate();
        $id2 = EventId::generate();

        $this->assertNotEquals($id1->toString(), $id2->toString());
        $this->assertIsString($id1->toString());
        $this->assertEquals(26, strlen($id1->toString())); // ULID length
    }

    #[Test]
    public function it_creates_from_valid_string(): void
    {
        $ulidString = '01HKQJ9V8XQZ3Y2W1P0RN6HTME';
        $eventId = EventId::fromString($ulidString);

        $this->assertEquals($ulidString, $eventId->toString());
    }

    #[Test]
    public function it_throws_exception_for_invalid_string(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid ULID format');

        EventId::fromString('invalid-ulid');
    }

    #[Test]
    public function it_compares_equality_correctly(): void
    {
        $id1 = EventId::fromString('01HKQJ9V8XQZ3Y2W1P0RN6HTME');
        $id2 = EventId::fromString('01HKQJ9V8XQZ3Y2W1P0RN6HTME');
        $id3 = EventId::fromString('01HKQJ9V8XQZ3Y2W1P0RN6HTMF');

        $this->assertTrue($id1->equals($id2));
        $this->assertFalse($id1->equals($id3));
    }

    #[Test]
    public function it_is_immutable(): void
    {
        $id = EventId::generate();
        $originalString = $id->toString();

        // Attempt to modify through reflection should not change original
        $reflection = new \ReflectionClass($id);
        $this->assertTrue($reflection->isReadOnly());
        
        $this->assertEquals($originalString, $id->toString());
    }

    #[Test]
    public function it_converts_to_string_directly(): void
    {
        $ulidString = '01HKQJ9V8XQZ3Y2W1P0RN6HTME';
        $eventId = EventId::fromString($ulidString);

        $this->assertEquals($ulidString, (string) $eventId);
    }

    #[Test]
    public function it_generates_monotonically_increasing_ids(): void
    {
        $ids = [];
        for ($i = 0; $i < 10; $i++) {
            $ids[] = EventId::generate()->toString();
            usleep(1000); // 1ms delay to ensure different timestamps
        }

        $sorted = $ids;
        sort($sorted);

        $this->assertEquals($sorted, $ids, 'Generated IDs should be monotonically increasing');
    }
}
