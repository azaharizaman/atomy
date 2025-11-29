<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Tests\Unit\Exceptions;

use Nexus\Backoffice\Exceptions\CircularReferenceException;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for CircularReferenceException.
 */
class CircularReferenceExceptionTest extends TestCase
{
    public function test_exception_message_includes_entity_type(): void
    {
        $exception = new CircularReferenceException('department', 'dept-123', 'dept-456');
        $this->assertStringContainsString('department', $exception->getMessage());
    }

    public function test_exception_message_includes_entity_id(): void
    {
        $exception = new CircularReferenceException('department', 'dept-123', 'dept-456');
        $this->assertStringContainsString('dept-123', $exception->getMessage());
    }

    public function test_exception_message_includes_proposed_parent_id(): void
    {
        $exception = new CircularReferenceException('department', 'dept-123', 'dept-456');
        $this->assertStringContainsString('dept-456', $exception->getMessage());
    }

    public function test_exception_extends_exception(): void
    {
        $exception = new CircularReferenceException('department', 'dept-123', 'dept-456');
        $this->assertInstanceOf(\Exception::class, $exception);
    }

    public function test_for_supervisor_hierarchy(): void
    {
        $exception = new CircularReferenceException('supervisor', 'staff-001', 'staff-002');
        $this->assertStringContainsString('supervisor', $exception->getMessage());
        $this->assertStringContainsString('staff-001', $exception->getMessage());
        $this->assertStringContainsString('staff-002', $exception->getMessage());
    }
}
