<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Tests\Unit\Coordinators;

use Nexus\Procurement\Contracts\GoodsReceiptPersistInterface;
use Nexus\Procurement\Contracts\PurchaseOrderQueryInterface;
use Nexus\ProcurementOperations\Coordinators\ServiceReceiptCoordinator;
use Nexus\ProcurementOperations\DTOs\ServiceReceiptRequest;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

#[CoversClass(ServiceReceiptCoordinator::class)]
final class ServiceReceiptCoordinatorTest extends TestCase
{
    private PurchaseOrderQueryInterface&MockObject $poQueryMock;
    private GoodsReceiptPersistInterface&MockObject $grPersistMock;
    private ServiceReceiptCoordinator $coordinator;

    protected function setUp(): void
    {
        $this->poQueryMock = $this->createMock(PurchaseOrderQueryInterface::class);
        $this->grPersistMock = $this->createMock(GoodsReceiptPersistInterface::class);

        $this->coordinator = new ServiceReceiptCoordinator(
            poQuery: $this->poQueryMock,
            grPersist: $this->grPersistMock,
            logger: new NullLogger(),
        );
    }

    #[Test]
    public function recordUsesTenantScopedPurchaseOrderLookup(): void
    {
        $request = new ServiceReceiptRequest(
            tenantId: 'tenant-1',
            purchaseOrderId: 'po-123',
            lineItems: [[
                'poLineId' => 'line-1',
                'amountCents' => 1000,
                'description' => 'Consulting service',
                'serviceDate' => '2026-04-11',
            ]],
            recordedBy: 'user-1',
        );

        $this->poQueryMock->expects($this->once())
            ->method('findById')
            ->with('tenant-1', 'po-123')
            ->willReturn(null);
        $this->grPersistMock->expects($this->never())->method('create');

        $result = $this->coordinator->record($request);

        $this->assertFalse($result->success);
        $this->assertSame('Purchase order not found.', $result->message);
    }
}
