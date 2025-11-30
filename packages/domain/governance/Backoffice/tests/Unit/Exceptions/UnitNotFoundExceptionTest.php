<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Tests\Unit\Exceptions;

use Nexus\Backoffice\Exceptions\UnitNotFoundException;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for UnitNotFoundException.
 */
class UnitNotFoundExceptionTest extends TestCase
{
    public function test_exception_message_includes_identifier(): void
    {
        $exception = new UnitNotFoundException('unit-123');
        $this->assertStringContainsString('unit-123', $exception->getMessage());
    }

    public function test_exception_message_includes_type(): void
    {
        $exception = new UnitNotFoundException('unit-123', 'code');
        $this->assertStringContainsString('code', $exception->getMessage());
    }

    public function test_exception_extends_exception(): void
    {
        $exception = new UnitNotFoundException('unit-123');
        $this->assertInstanceOf(\Exception::class, $exception);
    }

    public function test_default_type_is_id(): void
    {
        $exception = new UnitNotFoundException('unit-123');
        $this->assertStringContainsString('id', strtolower($exception->getMessage()));
    }
}
