<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Tests\Unit\Exceptions;

use Nexus\Backoffice\Exceptions\StaffNotFoundException;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for StaffNotFoundException.
 */
class StaffNotFoundExceptionTest extends TestCase
{
    public function test_exception_message_includes_identifier(): void
    {
        $exception = new StaffNotFoundException('staff-123');
        $this->assertStringContainsString('staff-123', $exception->getMessage());
    }

    public function test_exception_message_includes_type(): void
    {
        $exception = new StaffNotFoundException('staff-123', 'employee_id');
        $this->assertStringContainsString('employee_id', $exception->getMessage());
    }

    public function test_exception_extends_exception(): void
    {
        $exception = new StaffNotFoundException('staff-123');
        $this->assertInstanceOf(\Exception::class, $exception);
    }

    public function test_default_type_is_id(): void
    {
        $exception = new StaffNotFoundException('staff-123');
        $this->assertStringContainsString('id', strtolower($exception->getMessage()));
    }
}
