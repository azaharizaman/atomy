<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Tests\Unit\Coordinators;

use Nexus\Procurement\Contracts\GoodsReceiptNoteInterface;
use Nexus\Procurement\Contracts\GoodsReceiptQueryInterface;
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
    #[Test]
    public function it_uses_tenant_scoped_purchase_order_lookup(): void
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

        $purchaseOrderQueryStub = $this->createMock(PurchaseOrderQueryInterface::class);
        $purchaseOrderQueryStub->method('findById')
            ->willReturn(null);

        $goodsReceiptQueryStub = $this->createMock(GoodsReceiptQueryInterface::class);
        $goodsReceiptQueryStub->method('findByTenantAndId')
            ->willReturn(null);

        $contextProvider = new GoodsReceiptContextProvider(
            goodsReceiptQuery: $goodsReceiptQueryStub,
            purchaseOrderQuery: $purchaseOrderQueryStub,
        );

        $ruleRegistry = new GoodsReceiptRuleRegistry();

        $accrualServiceStub = $this->createMock(AccrualCalculationService::class);

        $goodsReceiptPersistStub = $this->createMock(\Nexus\Procurement\Contracts\GoodsReceiptPersistInterface::class);
        $goodsReceiptPersistStub->method('create')
            ->willReturn($this->createMock(GoodsReceiptNoteInterface::class));
        $goodsReceiptPersistStub->method('reverse')
            ->willReturn(null);

        $coordinator = new GoodsReceiptCoordinator(
            contextProvider: $contextProvider,
            ruleRegistry: $ruleRegistry,
            accrualService: $accrualServiceStub,
            purchaseOrderQuery: $purchaseOrderQueryStub,
            goodsReceiptPersist: $goodsReceiptPersistStub,
            logger: new NullLogger(),
        );

        $result = $coordinator->record($request);

        $this->assertFalse($result->success);
        $this->assertSame('Purchase order not found: po-123', $result->message);
        $this->assertSame(['tenant-1', 'po-123'], $purchaseOrderQueryStub->lastFindArgs);
        $this->assertFalse($goodsReceiptPersistStub->createCalled);
    }

    #[Test]
    public function it_fails_when_purchase_order_is_not_open(): void
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

        $purchaseOrderStub = new class {
            public function getStatus() { return 'closed'; }
            public function getId(): string { return 'po-123'; }
            public function getVendorId(): string { return 'vendor-1'; }
            public function getCurrency(): string { return 'USD'; }
        };

        $purchaseOrderQueryStub = new class($purchaseOrderStub) {
            public function __construct(private $po) {}
            public function findById(string $tenantId, string $id): ?object {
                return $this->po;
            }
        };

        $goodsReceiptQueryStub = new class {
            public function findByTenantAndId(string $tenantId, string $id): ?object {
                return null;
            }
        };

        $contextProvider = new GoodsReceiptContextProvider(
            goodsReceiptQuery: $goodsReceiptQueryStub,
            purchaseOrderQuery: $purchaseOrderQueryStub,
        );

        $goodsReceiptPersistStub = new class {
            public bool $createCalled = false;
            public function create(string $tenantId, string $purchaseOrderId, string $receiverId, array $data): object {
                $this->createCalled = true;
                return new class {
                    public function getId(): string { return 'gr-1'; }
                };
            }
        };

        $coordinator = new GoodsReceiptCoordinator(
            contextProvider: $contextProvider,
            ruleRegistry: new GoodsReceiptRuleRegistry(),
            accrualService: $this->createMock(AccrualCalculationService::class),
            purchaseOrderQuery: $purchaseOrderQueryStub,
            goodsReceiptPersist: $goodsReceiptPersistStub,
            logger: new NullLogger(),
        );

        $result = $coordinator->record($request);

        $this->assertFalse($result->success);
        $this->assertStringContainsString('not open', $result->message);
        $this->assertFalse($goodsReceiptPersistStub->createCalled);
    }

    #[Test]
    public function it_asserts_tenant_on_goods_receipt_lookup(): void
    {
        $goodsReceiptQueryStub = new class {
            public ?array $lastFindArgs = null;
            public function findByTenantAndId(string $tenantId, string $id): ?object {
                $this->lastFindArgs = [$tenantId, $id];
                return null;
            }
        };

        $purchaseOrderQueryStub = $this->createMock(PurchaseOrderQueryInterface::class);
        $purchaseOrderQueryStub->method('findById')
            ->willReturn(null);

        $contextProvider = new GoodsReceiptContextProvider(
            goodsReceiptQuery: $goodsReceiptQueryStub,
            purchaseOrderQuery: $purchaseOrderQueryStub,
        );

        $contextProvider->getContext('tenant-abc', 'gr-123');

        $this->assertSame(['tenant-abc', 'gr-123'], $goodsReceiptQueryStub->lastFindArgs);
    }
}
