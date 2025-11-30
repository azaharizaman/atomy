<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Tests\Unit\Exceptions;

use Nexus\Backoffice\Exceptions\InvalidOperationException;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for InvalidOperationException.
 */
class InvalidOperationExceptionTest extends TestCase
{
    public function test_inactive_entity_includes_entity_type(): void
    {
        $exception = InvalidOperationException::inactiveEntity('company', 'comp-123');
        $this->assertStringContainsString('company', $exception->getMessage());
    }

    public function test_inactive_entity_includes_entity_id(): void
    {
        $exception = InvalidOperationException::inactiveEntity('company', 'comp-123');
        $this->assertStringContainsString('comp-123', $exception->getMessage());
    }

    public function test_has_active_children_includes_entity_type(): void
    {
        $exception = InvalidOperationException::hasActiveChildren('department', 'dept-123');
        $this->assertStringContainsString('department', $exception->getMessage());
    }

    public function test_has_active_children_includes_entity_id(): void
    {
        $exception = InvalidOperationException::hasActiveChildren('department', 'dept-123');
        $this->assertStringContainsString('dept-123', $exception->getMessage());
    }

    public function test_has_active_staff_includes_entity_type(): void
    {
        $exception = InvalidOperationException::hasActiveStaff('office', 'office-123');
        $this->assertStringContainsString('office', $exception->getMessage());
    }

    public function test_has_active_staff_includes_entity_id(): void
    {
        $exception = InvalidOperationException::hasActiveStaff('office', 'office-123');
        $this->assertStringContainsString('office-123', $exception->getMessage());
    }

    public function test_exception_extends_exception(): void
    {
        $exception = InvalidOperationException::inactiveEntity('company', 'comp-123');
        $this->assertInstanceOf(\Exception::class, $exception);
    }

    public function test_inactive_entity_indicates_inactive_status(): void
    {
        $exception = InvalidOperationException::inactiveEntity('company', 'comp-123');
        $this->assertStringContainsString('inactive', strtolower($exception->getMessage()));
    }

    public function test_has_active_children_indicates_children_issue(): void
    {
        $exception = InvalidOperationException::hasActiveChildren('department', 'dept-123');
        $this->assertStringContainsString('children', strtolower($exception->getMessage()));
    }

    public function test_has_active_staff_indicates_staff_issue(): void
    {
        $exception = InvalidOperationException::hasActiveStaff('office', 'office-123');
        $this->assertStringContainsString('staff', strtolower($exception->getMessage()));
    }
}
