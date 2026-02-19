<?php

declare(strict_types=1);

namespace Nexus\CRM\Tests\Unit\Exceptions;

use Nexus\CRM\Exceptions\CRMException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class CRMExceptionTest extends TestCase
{
    #[Test]
    public function it_extends_base_exception(): void
    {
        $exception = new CRMException();

        $this->assertInstanceOf(\Exception::class, $exception);
    }

    #[Test]
    public function it_creates_with_default_values(): void
    {
        $exception = new CRMException();

        $this->assertSame('', $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }

    #[Test]
    public function it_creates_with_custom_message(): void
    {
        $exception = new CRMException('Something went wrong');

        $this->assertSame('Something went wrong', $exception->getMessage());
    }

    #[Test]
    public function it_creates_with_custom_code(): void
    {
        $exception = new CRMException('Error', 500);

        $this->assertSame('Error', $exception->getMessage());
        $this->assertSame(500, $exception->getCode());
    }

    #[Test]
    public function it_creates_with_previous_exception(): void
    {
        $previous = new \RuntimeException('Previous error');
        $exception = new CRMException('Current error', 0, $previous);

        $this->assertSame('Current error', $exception->getMessage());
        $this->assertSame($previous, $exception->getPrevious());
    }

    #[Test]
    public function it_can_be_thrown_and_caught(): void
    {
        $this->expectException(CRMException::class);
        $this->expectExceptionMessage('Test exception');

        throw new CRMException('Test exception');
    }

    #[Test]
    public function it_can_be_caught_as_generic_exception(): void
    {
        $caught = false;

        try {
            throw new CRMException('Test');
        } catch (\Exception $e) {
            $caught = true;
            $this->assertInstanceOf(CRMException::class, $e);
        }

        $this->assertTrue($caught);
    }

    #[Test]
    public function it_preserves_stack_trace(): void
    {
        $exception = new CRMException('With trace');

        $this->assertNotEmpty($exception->getTrace());
        $this->assertStringContainsString(__FUNCTION__, $exception->getTraceAsString());
    }

    #[Test]
    public function it_can_be_serialized(): void
    {
        $original = new CRMException('Serializable error', 404);
        $serialized = serialize($original);
        $unserialized = unserialize($serialized);

        $this->assertInstanceOf(CRMException::class, $unserialized);
        $this->assertSame('Serializable error', $unserialized->getMessage());
        $this->assertSame(404, $unserialized->getCode());
    }

    #[Test]
    public function it_returns_string_representation(): void
    {
        $exception = new CRMException('Test error');

        $stringRepresentation = (string) $exception;

        $this->assertStringContainsString('Test error', $stringRepresentation);
        $this->assertStringContainsString(CRMException::class, $stringRepresentation);
    }
}
