<?php

declare(strict_types=1);

namespace Nexus\EventStream\Tests\Unit\ValueObjects;

use Nexus\EventStream\ValueObjects\StreamId;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Group;

#[Group('eventstream')]
#[Group('value-objects')]
final class StreamIdTest extends TestCase
{
    #[Test]
    public function it_creates_from_valid_string(): void
    {
        $id = 'stream-123';
        $streamId = StreamId::fromString($id);

        $this->assertEquals($id, $streamId->toString());
    }

    #[Test]
    public function it_throws_exception_for_empty_string(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Stream ID cannot be empty');

        StreamId::fromString('');
    }

    #[Test]
    public function it_compares_equality_correctly(): void
    {
        $id1 = StreamId::fromString('stream-123');
        $id2 = StreamId::fromString('stream-123');
        $id3 = StreamId::fromString('stream-456');

        $this->assertTrue($id1->equals($id2));
        $this->assertFalse($id1->equals($id3));
    }

    #[Test]
    public function it_converts_to_string_directly(): void
    {
        $id = 'stream-789';
        $streamId = StreamId::fromString($id);

        $this->assertEquals($id, (string) $streamId);
    }

    #[Test]
    public function it_is_immutable(): void
    {
        $streamId = StreamId::fromString('stream-123');
        $reflection = new \ReflectionClass($streamId);

        $this->assertTrue($reflection->isReadOnly());
    }

    #[Test]
    public function it_handles_different_formats(): void
    {
        $formats = [
            'simple' => 'stream-1',
            'ulid' => '01HKQJ9V8XQZ3Y2W1P0RN6HTME',
            'uuid' => '550e8400-e29b-41d4-a716-446655440000',
            'composite' => 'tenant-123:account-456',
        ];

        foreach ($formats as $name => $format) {
            $streamId = StreamId::fromString($format);
            $this->assertEquals($format, $streamId->toString(), "Failed for format: {$name}");
        }
    }
}
