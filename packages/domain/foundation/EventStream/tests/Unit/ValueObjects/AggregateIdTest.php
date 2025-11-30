<?php

declare(strict_types=1);

namespace Nexus\EventStream\Tests\Unit\ValueObjects;

use Nexus\EventStream\ValueObjects\AggregateId;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Group;

#[Group('eventstream')]
#[Group('value-objects')]
final class AggregateIdTest extends TestCase
{
    #[Test]
    public function it_creates_from_valid_string(): void
    {
        $id = 'aggregate-123';
        $aggregateId = AggregateId::fromString($id);

        $this->assertEquals($id, $aggregateId->toString());
    }

    #[Test]
    public function it_throws_exception_for_empty_string(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Aggregate ID cannot be empty');

        AggregateId::fromString('');
    }

    #[Test]
    public function it_compares_equality_correctly(): void
    {
        $id1 = AggregateId::fromString('aggregate-123');
        $id2 = AggregateId::fromString('aggregate-123');
        $id3 = AggregateId::fromString('aggregate-456');

        $this->assertTrue($id1->equals($id2));
        $this->assertFalse($id1->equals($id3));
    }

    #[Test]
    public function it_converts_to_string_directly(): void
    {
        $id = 'aggregate-789';
        $aggregateId = AggregateId::fromString($id);

        $this->assertEquals($id, (string) $aggregateId);
    }

    #[Test]
    public function it_is_immutable(): void
    {
        $aggregateId = AggregateId::fromString('aggregate-123');
        $reflection = new \ReflectionClass($aggregateId);

        $this->assertTrue($reflection->isReadOnly());
    }

    #[Test]
    public function it_handles_ulid_format(): void
    {
        $ulid = '01HKQJ9V8XQZ3Y2W1P0RN6HTME';
        $aggregateId = AggregateId::fromString($ulid);

        $this->assertEquals($ulid, $aggregateId->toString());
    }

    #[Test]
    public function it_handles_uuid_format(): void
    {
        $uuid = '550e8400-e29b-41d4-a716-446655440000';
        $aggregateId = AggregateId::fromString($uuid);

        $this->assertEquals($uuid, $aggregateId->toString());
    }

    #[Test]
    public function it_preserves_exact_string(): void
    {
        $customId = 'custom-prefix-12345-suffix';
        $aggregateId = AggregateId::fromString($customId);

        $this->assertEquals($customId, $aggregateId->toString());
    }
}
