<?php

declare(strict_types=1);

namespace Nexus\Procurement\Tests\Unit\Exceptions;

use Nexus\Procurement\Exceptions\BudgetExceededException;
use PHPUnit\Framework\TestCase;

final class BudgetExceededExceptionTest extends TestCase
{
    public function test_po_exceeds_requisition_returns_correct_message(): void
    {
        $e = BudgetExceededException::poExceedsRequisition('PO-001', 1200.0, 1000.0, 10.0);

        self::assertStringContainsString('PO-001', $e->getMessage());
        self::assertStringContainsString('1200', $e->getMessage());
        self::assertStringContainsString('1000', $e->getMessage());
    }

    public function test_blanket_po_release_exceeds_total_returns_correct_message(): void
    {
        $e = BudgetExceededException::blanketPoReleaseExceedsTotal('BPO-1', 5000.0, 3000.0);

        self::assertStringContainsString('BPO-1', $e->getMessage());
        self::assertStringContainsString('5000', $e->getMessage());
        self::assertStringContainsString('3000', $e->getMessage());
    }
}
