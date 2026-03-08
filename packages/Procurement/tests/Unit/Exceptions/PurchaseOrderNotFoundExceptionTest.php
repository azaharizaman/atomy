<?php

declare(strict_types=1);

namespace Nexus\Procurement\Tests\Unit\Exceptions;

use Nexus\Procurement\Exceptions\ProcurementException;
use Nexus\Procurement\Exceptions\PurchaseOrderNotFoundException;
use PHPUnit\Framework\TestCase;

final class PurchaseOrderNotFoundExceptionTest extends TestCase
{
    public function test_for_id_returns_exception_with_message(): void
    {
        $e = PurchaseOrderNotFoundException::forId('po-123');

        self::assertInstanceOf(PurchaseOrderNotFoundException::class, $e);
        self::assertInstanceOf(ProcurementException::class, $e);
        self::assertSame("Purchase order with ID 'po-123' not found.", $e->getMessage());
    }

    public function test_for_number_returns_exception_with_message(): void
    {
        $e = PurchaseOrderNotFoundException::forNumber('tenant-1', 'PO-001');

        self::assertInstanceOf(PurchaseOrderNotFoundException::class, $e);
        self::assertSame("Purchase order 'PO-001' not found for tenant 'tenant-1'.", $e->getMessage());
    }
}
