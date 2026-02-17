<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Tests\Unit\Coordinators;

use Nexus\ProcurementOperations\Coordinators\PurchaseOrderCoordinator;
use Nexus\ProcurementOperations\DTOs\CreatePurchaseOrderRequest;
use Nexus\ProcurementOperations\DTOs\PurchaseOrderResult;
use Nexus\Procurement\Contracts\PurchaseOrderManagerInterface;
use Nexus\Procurement\Contracts\PurchaseOrderQueryInterface;
use Nexus\Procurement\Contracts\RequisitionQueryInterface;
use Nexus\Procurement\Contracts\RequisitionInterface;
use Nexus\Procurement\Contracts\PurchaseOrderInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

#[CoversClass(PurchaseOrderCoordinator::class)]
final class PurchaseOrderCoordinatorTest extends TestCase
{
    private PurchaseOrderManagerInterface&MockObject $managerMock;
    private PurchaseOrderQueryInterface&MockObject $poQueryMock;
    private RequisitionQueryInterface&MockObject $reqQueryMock;
    private PurchaseOrderCoordinator $coordinator;

    protected function setUp(): void
    {
        $this->managerMock = $this->createMock(PurchaseOrderManagerInterface::class);
        $this->poQueryMock = $this->createMock(PurchaseOrderQueryInterface::class);
        $this->reqQueryMock = $this->createMock(RequisitionQueryInterface::class);

        $this->coordinator = new PurchaseOrderCoordinator(
            poManager: $this->managerMock,
            poQuery: $this->poQueryMock,
            requisitionQuery: $this->reqQueryMock,
            logger: new NullLogger()
        );
    }

    #[Test]
    public function createFromRequisition_creates_po(): void
    {
        $request = new CreatePurchaseOrderRequest(
            tenantId: 'tenant-1',
            requisitionId: 'req-123',
            vendorId: 'vendor-1',
            createdBy: 'user-1'
        );

        $requisitionMock = $this->createMock(RequisitionInterface::class);
        $statusReqMock = new \stdClass();
        $statusReqMock->value = 'approved';
        $requisitionMock->method('getStatus')->willReturn($statusReqMock);

        $this->reqQueryMock->method('findById')->with('req-123')->willReturn($requisitionMock);

        $poMock = $this->createMock(PurchaseOrderInterface::class);
        $poMock->method('getId')->willReturn('po-123');
        $poMock->method('getNumber')->willReturn('PO-001');
        
        $statusPoMock = new \stdClass();
        $statusPoMock->value = 'open';
        $poMock->method('getStatus')->willReturn($statusPoMock);
        
        $poMock->method('getTotalAmountCents')->willReturn(100);
        $poMock->method('getVendorId')->willReturn('vendor-1');

        $this->managerMock->expects($this->once())
            ->method('createFromRequisition')
            ->willReturn($poMock);

        $result = $this->coordinator->createFromRequisition($request);

        $this->assertTrue($result->success);
        $this->assertSame('po-123', $result->purchaseOrderId);
    }
}
