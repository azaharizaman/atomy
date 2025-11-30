<?php

declare(strict_types=1);

namespace Nexus\EventStream\Tests\Unit\Exceptions;

use Nexus\EventStream\Exceptions\EventStreamException;
use Nexus\EventStream\Exceptions\StreamNotFoundException;
use Nexus\EventStream\Exceptions\SnapshotNotFoundException;
use Nexus\EventStream\Exceptions\InvalidSnapshotException;
use Nexus\EventStream\Exceptions\ProjectionException;
use Nexus\EventStream\Exceptions\EventSerializationException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\DataProvider;

#[Group('eventstream')]
#[Group('exceptions')]
final class ExceptionHierarchyTest extends TestCase
{
    #[Test]
    public function base_exception_extends_php_exception(): void
    {
        $exception = new EventStreamException('Test message');

        $this->assertInstanceOf(\Exception::class, $exception);
    }

    #[Test]
    public function stream_not_found_exception_extends_base(): void
    {
        $exception = new StreamNotFoundException('stream-123');

        $this->assertInstanceOf(EventStreamException::class, $exception);
        $this->assertInstanceOf(\Exception::class, $exception);
    }

    #[Test]
    public function snapshot_not_found_exception_extends_base(): void
    {
        $exception = new SnapshotNotFoundException('aggregate-456');

        $this->assertInstanceOf(EventStreamException::class, $exception);
        $this->assertInstanceOf(\Exception::class, $exception);
    }

    #[Test]
    public function invalid_snapshot_exception_extends_base(): void
    {
        $exception = new InvalidSnapshotException('aggregate-789', 'checksum mismatch');

        $this->assertInstanceOf(EventStreamException::class, $exception);
        $this->assertInstanceOf(\Exception::class, $exception);
    }

    #[Test]
    public function projection_exception_extends_base(): void
    {
        $exception = new ProjectionException('CustomerProjector', 'event-123');

        $this->assertInstanceOf(EventStreamException::class, $exception);
        $this->assertInstanceOf(\Exception::class, $exception);
    }

    #[Test]
    public function event_serialization_exception_extends_base(): void
    {
        $exception = new EventSerializationException('AccountDebitedEvent');

        $this->assertInstanceOf(EventStreamException::class, $exception);
        $this->assertInstanceOf(\Exception::class, $exception);
    }

    #[Test]
    public function stream_not_found_exception_has_descriptive_message(): void
    {
        $exception = new StreamNotFoundException('stream-123');

        $this->assertStringContainsString('stream-123', $exception->getMessage());
        $this->assertStringContainsString('not found', strtolower($exception->getMessage()));
    }

    #[Test]
    public function snapshot_not_found_exception_has_descriptive_message(): void
    {
        $exception = new SnapshotNotFoundException('aggregate-456');

        $this->assertStringContainsString('aggregate-456', $exception->getMessage());
        $this->assertStringContainsString('snapshot', strtolower($exception->getMessage()));
    }

    #[Test]
    public function invalid_snapshot_exception_includes_checksum_info(): void
    {
        $exception = new InvalidSnapshotException(
            'snapshot-789',
            'Checksum mismatch: expected expected-hash, got actual-hash'
        );

        $message = $exception->getMessage();
        $this->assertStringContainsString('snapshot-789', $message);
        $this->assertStringContainsString('expected-hash', $message);
        $this->assertStringContainsString('actual-hash', $message);
        $this->assertStringContainsString('Checksum', $message);
    }

    #[Test]
    public function projection_exception_supports_previous_exception(): void
    {
        $previous = new \RuntimeException('Database error');
        $exception = new ProjectionException('CustomerProjector', 'event-456', '', 0, $previous);

        $this->assertSame($previous, $exception->getPrevious());
        $this->assertStringContainsString('CustomerProjector', $exception->getMessage());
        $this->assertStringContainsString('event-456', $exception->getMessage());
    }

    #[Test]
    public function event_serialization_exception_includes_event_type(): void
    {
        // Test with default message (should include event type)
        $exception = new EventSerializationException('AccountDebitedEvent');
        $this->assertStringContainsString('AccountDebitedEvent', $exception->getMessage());

        // Test with custom message (custom message takes precedence)
        $exceptionWithCustomMessage = new EventSerializationException('AccountDebitedEvent', 'Invalid JSON: unexpected token');
        $this->assertStringContainsString('Invalid JSON', $exceptionWithCustomMessage->getMessage());
        $this->assertStringContainsString('AccountDebitedEvent', $exceptionWithCustomMessage->getMessage());
    }
}
