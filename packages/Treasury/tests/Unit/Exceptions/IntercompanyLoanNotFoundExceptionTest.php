<?php

declare(strict_types=1);

namespace Nexus\Treasury\Tests\Unit\Exceptions;

use Nexus\Treasury\Exceptions\IntercompanyLoanNotFoundException;
use PHPUnit\Framework\TestCase;

final class IntercompanyLoanNotFoundExceptionTest extends TestCase
{
    public function test_for_id_creates_exception(): void
    {
        $exception = IntercompanyLoanNotFoundException::forId('LOAN-001');

        $this->assertStringContainsString('LOAN-001', $exception->getMessage());
    }

    public function test_for_entities_creates_exception(): void
    {
        $exception = IntercompanyLoanNotFoundException::forEntities('entity-001', 'entity-002');

        $this->assertStringContainsString('entity-001', $exception->getMessage());
        $this->assertStringContainsString('entity-002', $exception->getMessage());
    }

    public function test_for_tenant_creates_exception(): void
    {
        $exception = IntercompanyLoanNotFoundException::forTenant('tenant-001');

        $this->assertStringContainsString('tenant-001', $exception->getMessage());
    }
}
