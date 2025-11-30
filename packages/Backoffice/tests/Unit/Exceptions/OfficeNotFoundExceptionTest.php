<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Tests\Unit\Exceptions;

use Nexus\Backoffice\Exceptions\OfficeNotFoundException;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for OfficeNotFoundException.
 */
class OfficeNotFoundExceptionTest extends TestCase
{
    public function test_exception_message_includes_identifier(): void
    {
        $exception = new OfficeNotFoundException('office-123');
        $this->assertStringContainsString('office-123', $exception->getMessage());
    }

    public function test_exception_message_includes_type(): void
    {
        $exception = new OfficeNotFoundException('office-123', 'code');
        $this->assertStringContainsString('code', $exception->getMessage());
    }

    public function test_exception_extends_exception(): void
    {
        $exception = new OfficeNotFoundException('office-123');
        $this->assertInstanceOf(\Exception::class, $exception);
    }

    public function test_default_type_is_id(): void
    {
        $exception = new OfficeNotFoundException('office-123');
        $this->assertStringContainsString('id', strtolower($exception->getMessage()));
    }
}
