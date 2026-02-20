<?php

declare(strict_types=1);

namespace Nexus\Treasury\Tests\Unit\Exceptions;

use Nexus\Treasury\Exceptions\TreasuryApprovalNotFoundException;
use PHPUnit\Framework\TestCase;

final class TreasuryApprovalNotFoundExceptionTest extends TestCase
{
    public function test_for_id_creates_exception(): void
    {
        $exception = TreasuryApprovalNotFoundException::forId('APP-001');

        $this->assertStringContainsString('APP-001', $exception->getMessage());
    }

    public function test_for_transaction_creates_exception(): void
    {
        $exception = TreasuryApprovalNotFoundException::forTransaction('TXN-001');

        $this->assertStringContainsString('TXN-001', $exception->getMessage());
    }

    public function test_for_user_creates_exception(): void
    {
        $exception = TreasuryApprovalNotFoundException::forUser('user-001', 'APP-001');

        $this->assertStringContainsString('user-001', $exception->getMessage());
        $this->assertStringContainsString('APP-001', $exception->getMessage());
    }

    public function test_pending_not_found_creates_exception(): void
    {
        $exception = TreasuryApprovalNotFoundException::pendingNotFound('tenant-001');

        $this->assertStringContainsString('tenant-001', $exception->getMessage());
    }
}
