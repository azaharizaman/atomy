<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Tests\Unit\Coordinators;

use Nexus\Procurement\Contracts\GoodsReceiptPersistInterface;
use Nexus\Procurement\Contracts\PurchaseOrderQueryInterface;
use Nexus\ProcurementOperations\Coordinators\GoodsReceiptCoordinator;
use Nexus\ProcurementOperations\DataProviders\GoodsReceiptContextProvider;
use Nexus\ProcurementOperations\DTOs\RecordGoodsReceiptRequest;
use Nexus\ProcurementOperations\Rules\GoodsReceipt\GoodsReceiptRuleRegistry;
use Nexus\ProcurementOperations\Services\AccrualCalculationService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

#[CoversClass(GoodsReceiptCoordinator::class)]
final class GoodsReceiptCoordinatorTest extends TestCase
{
    private GoodsReceiptContextProvider&MockObject $contextProviderMock;
    private GoodsReceiptRuleRegistry&MockObject $ruleRegistryMock;
    private AccrualCalculationService&MockObject $accrualServiceMock;
    private PurchaseOrderQueryInterface&MockObject $poQueryMock;
    private GoodsReceiptPersistInterface&MockObject $grPersistMock;
    private GoodsReceiptCoordinator $coordinator;

    protected function setUp(): void
    {
        $this->contextProviderMock = $this->createMock(GoodsReceiptContextProvider::class);
        $this->ruleRegistryMock = $this->createMock(GoodsReceiptRuleRegistry::class);
        $this->accrualServiceMock = $this->createMock(AccrualCalculationService::class);
        $this->poQueryMock = $this->createMock(PurchaseOrderQueryInterface::class);
        $this->grPersistMock = $this->createMock(GoodsReceiptPersistInterface::class);

        $this->coordinator = new GoodsReceiptCoordinator(
            contextProvider: $this->contextProviderMock,
            ruleRegistry: $this->ruleRegistryMock,
            accrualService: $this->accrualServiceMock,
            purchaseOrderQuery: $this->poQueryMock,
            goodsReceiptPersist: $this->grPersistMock,
            logger: new NullLogger(),
        );
    }

    #[Test]
    public function recordUsesTenantScopedPurchaseOrderLookup(): void
    {
        $request = new RecordGoodsReceiptRequest(
            tenantId: 'tenant-1',
            purchaseOrderId: 'po-123',
            warehouseId: 'wh-1',
            receivedBy: 'user-1',
            lineItems: [[
                'poLineId' => 'line-1',
                'productId' => 'prod-1',
                'quantityReceived' => 5.0,
                'uom' => 'EA',
            ]],
        );

        $this->poQueryMock->expects($this->once())
            ->method('findById')
            ->with('tenant-1', 'po-123')
            ->willReturn(null);
        $this->contextProviderMock->expects($this->never())->method('getPreReceiptContext');
        $this->grPersistMock->expects($this->never())->method('create');

        $result = $this->coordinator->record($request);

        $this->assertFalse($result->success);
        $this->assertSame('Purchase order not found: po-123', $result->message);
    }
}
