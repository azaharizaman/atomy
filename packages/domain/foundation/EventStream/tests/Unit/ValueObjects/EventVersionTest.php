<?php

declare(strict_types=1);

namespace Nexus\EventStream\Tests\Unit\ValueObjects;

use Nexus\EventStream\ValueObjects\EventVersion;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\DataProvider;

#[Group('eventstream')]
#[Group('value-objects')]
final class EventVersionTest extends TestCase
{
    #[Test]
    public function it_creates_first_version(): void
    {
        $version = EventVersion::first();

        $this->assertEquals(1, $version->toInt());
    }

    #[Test]
    public function it_creates_from_valid_integer(): void
    {
        $version = EventVersion::fromInt(5);

        $this->assertEquals(5, $version->toInt());
    }

    #[Test]
    #[DataProvider('invalidVersionProvider')]
    public function it_throws_exception_for_invalid_version(int $invalidVersion): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Event version must be non-negative');

        EventVersion::fromInt($invalidVersion);
    }

    public static function invalidVersionProvider(): array
    {
        return [
            'negative' => [-1],
            'negative large' => [-100],
        ];
    }

    #[Test]
    public function it_increments_version_correctly(): void
    {
        $version = EventVersion::fromInt(5);
        $nextVersion = $version->next();

        $this->assertEquals(6, $nextVersion->toInt());
        $this->assertEquals(5, $version->toInt()); // Original unchanged (immutability)
    }

    #[Test]
    public function it_compares_equality_correctly(): void
    {
        $v1 = EventVersion::fromInt(5);
        $v2 = EventVersion::fromInt(5);
        $v3 = EventVersion::fromInt(6);

        $this->assertTrue($v1->equals($v2));
        $this->assertFalse($v1->equals($v3));
    }

    #[Test]
    public function it_compares_greater_than_correctly(): void
    {
        $v1 = EventVersion::fromInt(5);
        $v2 = EventVersion::fromInt(3);
        $v3 = EventVersion::fromInt(7);

        $this->assertTrue($v1->isGreaterThan($v2));
        $this->assertFalse($v1->isGreaterThan($v3));
        $this->assertFalse($v1->isGreaterThan($v1)); // Not greater than itself
    }

    #[Test]
    public function it_compares_less_than_correctly(): void
    {
        $v1 = EventVersion::fromInt(5);
        $v2 = EventVersion::fromInt(7);
        $v3 = EventVersion::fromInt(3);

        $this->assertTrue($v1->isLessThan($v2));
        $this->assertFalse($v1->isLessThan($v3));
        $this->assertFalse($v1->isLessThan($v1)); // Not less than itself
    }

    #[Test]
    public function it_is_immutable(): void
    {
        $version = EventVersion::fromInt(5);
        $reflection = new \ReflectionClass($version);

        $this->assertTrue($reflection->isReadOnly());
    }

    #[Test]
    public function it_converts_to_string(): void
    {
        $version = EventVersion::fromInt(42);

        $this->assertEquals('42', (string) $version);
    }

    #[Test]
    public function it_accepts_zero_as_valid_version(): void
    {
        $version = EventVersion::fromInt(0);

        $this->assertEquals(0, $version->toInt());
    }

    #[Test]
    public function it_handles_large_version_numbers(): void
    {
        $largeVersion = 999999;
        $version = EventVersion::fromInt($largeVersion);

        $this->assertEquals($largeVersion, $version->toInt());
    }
}
