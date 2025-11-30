<?php

declare(strict_types=1);

namespace Nexus\EventStream\Tests\Unit\Exceptions;

use Nexus\EventStream\Exceptions\ConcurrencyException;
use Nexus\EventStream\Exceptions\EventStreamException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Group;

#[Group('eventstream')]
#[Group('exceptions')]
final class ConcurrencyExceptionTest extends TestCase
{
    #[Test]
    public function it_extends_base_exception(): void
    {
        $exception = new ConcurrencyException('agg-123', 5, 7);

        $this->assertInstanceOf(EventStreamException::class, $exception);
        $this->assertInstanceOf(\Exception::class, $exception);
    }

    #[Test]
    public function it_stores_aggregate_id(): void
    {
        $aggregateId = 'aggregate-456';
        $exception = new ConcurrencyException($aggregateId, 5, 7);

        $this->assertEquals($aggregateId, $exception->getAggregateId());
    }

    #[Test]
    public function it_stores_expected_version(): void
    {
        $expectedVersion = 5;
        $exception = new ConcurrencyException('agg-123', $expectedVersion, 7);

        $this->assertEquals($expectedVersion, $exception->getExpectedVersion());
    }

    #[Test]
    public function it_stores_actual_version(): void
    {
        $actualVersion = 7;
        $exception = new ConcurrencyException('agg-123', 5, $actualVersion);

        $this->assertEquals($actualVersion, $exception->getActualVersion());
    }

    #[Test]
    public function it_has_descriptive_message(): void
    {
        $exception = new ConcurrencyException('agg-123', 5, 7);

        $this->assertStringContainsString('agg-123', $exception->getMessage());
        $this->assertStringContainsString('5', $exception->getMessage());
        $this->assertStringContainsString('7', $exception->getMessage());
        $this->assertStringContainsString('Concurrency', $exception->getMessage());
    }

    #[Test]
    public function it_provides_retry_guidance(): void
    {
        $exception = new ConcurrencyException('agg-123', 5, 7);

        $this->assertStringContainsString('expected', strtolower($exception->getMessage()));
        $this->assertStringContainsString('actual', strtolower($exception->getMessage()));
    }

    #[Test]
    public function it_handles_zero_versions(): void
    {
        $exception = new ConcurrencyException('agg-123', 0, 1);

        $this->assertEquals(0, $exception->getExpectedVersion());
        $this->assertEquals(1, $exception->getActualVersion());
    }

    #[Test]
    public function it_handles_large_version_numbers(): void
    {
        $expected = 999999;
        $actual = 1000000;
        $exception = new ConcurrencyException('agg-123', $expected, $actual);

        $this->assertEquals($expected, $exception->getExpectedVersion());
        $this->assertEquals($actual, $exception->getActualVersion());
    }
}
