<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Tests\Unit\Coordinators;

use Nexus\ProcurementOperations\Coordinators\GoodsReceiptCoordinator;
use Nexus\ProcurementOperations\DataProviders\GoodsReceiptContextProvider;
use Nexus\ProcurementOperations\DTOs\RecordGoodsReceiptRequest;
use Nexus\ProcurementOperations\Rules\GoodsReceipt\GoodsReceiptRuleRegistry;
use Nexus\ProcurementOperations\Services\AccrualCalculationService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
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

        $purchaseOrderQueryStub = new class {
            public ?array $lastFindArgs = null;

            public function findById(string $tenantId, string $id): ?object {
                $this->lastFindArgs = [$tenantId, $id];
                return null;
            }
        };

        $goodsReceiptQueryStub = new class {
            public function findById(string $id): ?object {
                return null;
            }
        };

        $contextProvider = new GoodsReceiptContextProvider(
            goodsReceiptQuery: $goodsReceiptQueryStub,
            purchaseOrderQuery: $purchaseOrderQueryStub,
        );

        $ruleRegistry = new GoodsReceiptRuleRegistry();

        $accrualServiceStub = new class {
            public function postGoodsReceiptAccrual(
                string $tenantId,
                string $goodsReceiptId,
                string $purchaseOrderId,
                array $lineItems,
                string $receivedBy
            ): string {
                return 'accrual-1';
            }
        };

        $goodsReceiptPersistStub = new class {
            public bool $createCalled = false;

            public function create(array $data): string {
                $this->createCalled = true;
                return 'gr-1';
            }

            public function reverse(string $goodsReceiptId, string $reversedBy, string $reason): void {
            }
        };

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
        };

        $purchaseOrderQueryStub = new class($purchaseOrderStub) {
            public function __construct(private $po) {}
            public function findById(string $tenantId, string $id): ?object {
                return $this->po;
            }
        };

        $goodsReceiptQueryStub = new class {
            public function findById(string $id): ?object {
                return null;
            }
        };

        $contextProvider = new GoodsReceiptContextProvider(
            goodsReceiptQuery: $goodsReceiptQueryStub,
            purchaseOrderQuery: $purchaseOrderQueryStub,
        );

        $goodsReceiptPersistStub = new class {
            public bool $createCalled = false;
            public function create(array $data): string {
                $this->createCalled = true;
                return 'gr-1';
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
}