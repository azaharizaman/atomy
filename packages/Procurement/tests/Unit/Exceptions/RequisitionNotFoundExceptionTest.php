<?php

declare(strict_types=1);

namespace Nexus\Procurement\Tests\Unit\Exceptions;

use Nexus\Procurement\Exceptions\RequisitionNotFoundException;
use PHPUnit\Framework\TestCase;

final class RequisitionNotFoundExceptionTest extends TestCase
{
    public function test_for_id_returns_correct_message(): void
    {
        $e = RequisitionNotFoundException::forId('req-123');

        self::assertStringContainsString('req-123', $e->getMessage());
    }

    public function test_for_number_returns_correct_message(): void
    {
        $e = RequisitionNotFoundException::forNumber('tenant-1', 'REQ-001');

        self::assertStringContainsString('tenant-1', $e->getMessage());
        self::assertStringContainsString('REQ-001', $e->getMessage());
    }
}
