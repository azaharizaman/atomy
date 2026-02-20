<?php

declare(strict_types=1);

namespace Nexus\Treasury\Tests\Unit\Exceptions;

use Nexus\Treasury\Exceptions\TreasuryPolicyNotFoundException;
use PHPUnit\Framework\TestCase;

final class TreasuryPolicyNotFoundExceptionTest extends TestCase
{
    public function test_for_id_creates_exception(): void
    {
        $exception = TreasuryPolicyNotFoundException::forId('POL-001');

        $this->assertStringContainsString('POL-001', $exception->getMessage());
    }

    public function test_for_tenant_creates_exception(): void
    {
        $exception = TreasuryPolicyNotFoundException::forTenant('tenant-001');

        $this->assertStringContainsString('tenant-001', $exception->getMessage());
    }

    public function test_for_name_creates_exception(): void
    {
        $exception = TreasuryPolicyNotFoundException::forName('Main Policy', 'tenant-001');

        $this->assertStringContainsString('Main Policy', $exception->getMessage());
        $this->assertStringContainsString('tenant-001', $exception->getMessage());
    }
}
