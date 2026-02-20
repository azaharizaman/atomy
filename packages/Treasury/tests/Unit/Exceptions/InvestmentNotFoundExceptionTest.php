<?php

declare(strict_types=1);

namespace Nexus\Treasury\Tests\Unit\Exceptions;

use Nexus\Treasury\Exceptions\InvestmentNotFoundException;
use PHPUnit\Framework\TestCase;

final class InvestmentNotFoundExceptionTest extends TestCase
{
    public function test_for_id_creates_exception(): void
    {
        $exception = InvestmentNotFoundException::forId('INV-001');

        $this->assertStringContainsString('INV-001', $exception->getMessage());
    }

    public function test_for_tenant_creates_exception(): void
    {
        $exception = InvestmentNotFoundException::forTenant('tenant-001');

        $this->assertStringContainsString('tenant-001', $exception->getMessage());
    }

    public function test_for_maturity_creates_exception(): void
    {
        $exception = InvestmentNotFoundException::forMaturity('INV-001');

        $this->assertStringContainsString('INV-001', $exception->getMessage());
        $this->assertStringContainsString('maturity', $exception->getMessage());
    }
}
