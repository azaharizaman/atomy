<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Tests\Unit\Exceptions;

use Nexus\Backoffice\Exceptions\DuplicateCodeException;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for DuplicateCodeException.
 */
class DuplicateCodeExceptionTest extends TestCase
{
    public function test_exception_message_includes_entity_type(): void
    {
        $exception = new DuplicateCodeException('company', 'COMP-001', 'global');
        $this->assertStringContainsString('company', $exception->getMessage());
    }

    public function test_exception_message_includes_code(): void
    {
        $exception = new DuplicateCodeException('company', 'COMP-001', 'global');
        $this->assertStringContainsString('COMP-001', $exception->getMessage());
    }

    public function test_exception_message_includes_scope(): void
    {
        $exception = new DuplicateCodeException('company', 'COMP-001', 'tenant-123');
        $this->assertStringContainsString('tenant-123', $exception->getMessage());
    }

    public function test_exception_extends_exception(): void
    {
        $exception = new DuplicateCodeException('company', 'COMP-001', 'global');
        $this->assertInstanceOf(\Exception::class, $exception);
    }

    public function test_for_different_entity_types(): void
    {
        $companyException = new DuplicateCodeException('company', 'CODE-1', 'scope');
        $officeException = new DuplicateCodeException('office', 'CODE-2', 'scope');
        $deptException = new DuplicateCodeException('department', 'CODE-3', 'scope');
        
        $this->assertStringContainsString('company', $companyException->getMessage());
        $this->assertStringContainsString('office', $officeException->getMessage());
        $this->assertStringContainsString('department', $deptException->getMessage());
    }
}
