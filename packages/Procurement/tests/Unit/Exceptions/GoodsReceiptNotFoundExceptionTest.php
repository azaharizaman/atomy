<?php

declare(strict_types=1);

namespace Nexus\Procurement\Tests\Unit\Exceptions;

use Nexus\Procurement\Exceptions\GoodsReceiptNotFoundException;
use Nexus\Procurement\Exceptions\ProcurementException;
use PHPUnit\Framework\TestCase;

final class GoodsReceiptNotFoundExceptionTest extends TestCase
{
    public function test_for_id_returns_exception_with_message(): void
    {
        $e = GoodsReceiptNotFoundException::forId('grn-123');

        self::assertInstanceOf(GoodsReceiptNotFoundException::class, $e);
        self::assertInstanceOf(ProcurementException::class, $e);
        self::assertSame("Goods receipt note with ID 'grn-123' not found.", $e->getMessage());
    }

    public function test_for_number_returns_exception_with_message(): void
    {
        $e = GoodsReceiptNotFoundException::forNumber('tenant-1', 'GRN-001');

        self::assertInstanceOf(GoodsReceiptNotFoundException::class, $e);
        self::assertSame("GRN 'GRN-001' not found for tenant 'tenant-1'.", $e->getMessage());
    }
}
