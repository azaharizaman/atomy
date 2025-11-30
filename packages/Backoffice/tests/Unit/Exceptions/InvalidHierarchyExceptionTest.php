<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Tests\Unit\Exceptions;

use Nexus\Backoffice\Exceptions\InvalidHierarchyException;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for InvalidHierarchyException.
 */
class InvalidHierarchyExceptionTest extends TestCase
{
    public function test_max_depth_exceeded_includes_entity_type(): void
    {
        $exception = InvalidHierarchyException::maxDepthExceeded('department', 8, 10);
        $this->assertStringContainsString('department', $exception->getMessage());
    }

    public function test_max_depth_exceeded_includes_max_depth(): void
    {
        $exception = InvalidHierarchyException::maxDepthExceeded('department', 8, 10);
        $this->assertStringContainsString('8', $exception->getMessage());
    }

    public function test_max_depth_exceeded_includes_current_depth(): void
    {
        $exception = InvalidHierarchyException::maxDepthExceeded('department', 8, 10);
        $this->assertStringContainsString('10', $exception->getMessage());
    }

    public function test_cross_boundary_includes_child_type(): void
    {
        $exception = InvalidHierarchyException::crossBoundary('department', 'company');
        $this->assertStringContainsString('department', $exception->getMessage());
    }

    public function test_cross_boundary_includes_parent_type(): void
    {
        $exception = InvalidHierarchyException::crossBoundary('department', 'company');
        $this->assertStringContainsString('company', $exception->getMessage());
    }

    public function test_exception_extends_exception(): void
    {
        $exception = InvalidHierarchyException::maxDepthExceeded('department', 8, 10);
        $this->assertInstanceOf(\Exception::class, $exception);
    }

    public function test_max_depth_exceeded_for_supervisor_hierarchy(): void
    {
        $exception = InvalidHierarchyException::maxDepthExceeded('supervisor', 15, 18);
        $this->assertStringContainsString('supervisor', $exception->getMessage());
        $this->assertStringContainsString('15', $exception->getMessage());
        $this->assertStringContainsString('18', $exception->getMessage());
    }
}
