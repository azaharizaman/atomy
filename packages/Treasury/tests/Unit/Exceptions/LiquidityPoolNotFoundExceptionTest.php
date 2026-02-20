<?php

declare(strict_types=1);

namespace Nexus\Treasury\Tests\Unit\Exceptions;

use Nexus\Treasury\Exceptions\LiquidityPoolNotFoundException;
use PHPUnit\Framework\TestCase;

final class LiquidityPoolNotFoundExceptionTest extends TestCase
{
    public function test_for_id_creates_exception(): void
    {
        $exception = LiquidityPoolNotFoundException::forId('POOL-001');

        $this->assertStringContainsString('POOL-001', $exception->getMessage());
    }

    public function test_for_tenant_creates_exception(): void
    {
        $exception = LiquidityPoolNotFoundException::forTenant('tenant-001');

        $this->assertStringContainsString('tenant-001', $exception->getMessage());
    }

    public function test_for_name_creates_exception(): void
    {
        $exception = LiquidityPoolNotFoundException::forName('Main Pool', 'tenant-001');

        $this->assertStringContainsString('Main Pool', $exception->getMessage());
        $this->assertStringContainsString('tenant-001', $exception->getMessage());
    }
}
