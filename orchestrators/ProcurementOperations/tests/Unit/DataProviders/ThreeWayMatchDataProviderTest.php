<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Tests\Unit\DataProviders;

use Nexus\Payable\Contracts\VendorBillInterface;
use Nexus\Payable\Contracts\VendorBillQueryInterface;
use Nexus\Procurement\Contracts\GoodsReceiptQueryInterface;
use Nexus\Procurement\Contracts\PurchaseOrderQueryInterface;
use Nexus\ProcurementOperations\DataProviders\ThreeWayMatchDataProvider;
use Nexus\ProcurementOperations\Exceptions\PurchaseOrderException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(ThreeWayMatchDataProvider::class)]
final class ThreeWayMatchDataProviderTest extends TestCase
{
    private PurchaseOrderQueryInterface&MockObject $purchaseOrderQueryMock;
    private GoodsReceiptQueryInterface&MockObject $goodsReceiptQueryMock;
    private VendorBillQueryInterface&MockObject $vendorBillQueryMock;
    private ThreeWayMatchDataProvider $provider;

    protected function setUp(): void
    {
        $this->purchaseOrderQueryMock = $this->createMock(PurchaseOrderQueryInterface::class);
        $this->goodsReceiptQueryMock = $this->createMock(GoodsReceiptQueryInterface::class);
        $this->vendorBillQueryMock = $this->createMock(VendorBillQueryInterface::class);

        $this->provider = new ThreeWayMatchDataProvider(
            purchaseOrderQuery: $this->purchaseOrderQueryMock,
            goodsReceiptQuery: $this->goodsReceiptQueryMock,
            vendorBillQuery: $this->vendorBillQueryMock,
        );
    }

    #[Test]
    public function buildContextUsesTenantScopedPurchaseOrderLookup(): void
    {
        $this->vendorBillQueryMock->expects($this->once())
            ->method('findById')
            ->with('bill-123')
            ->willReturn($this->createMock(VendorBillInterface::class));
        $this->purchaseOrderQueryMock->expects($this->once())
            ->method('findById')
            ->with('tenant-1', 'po-123')
            ->willReturn(null);
        $this->goodsReceiptQueryMock->expects($this->never())->method('findById');

        $this->expectException(PurchaseOrderException::class);
        $this->expectExceptionMessage('Purchase order not found: po-123');

        $this->provider->buildContext(
            tenantId: 'tenant-1',
            vendorBillId: 'bill-123',
            purchaseOrderId: 'po-123',
            goodsReceiptIds: ['gr-1'],
        );
    }
}
