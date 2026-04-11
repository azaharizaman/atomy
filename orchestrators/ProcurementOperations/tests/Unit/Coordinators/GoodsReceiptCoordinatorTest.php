<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Tests\Unit\Coordinators;

use Nexus\ProcurementOperations\Coordinators\GoodsReceiptCoordinator;
use Nexus\ProcurementOperations\DTOs\RecordGoodsReceiptRequest;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

#[CoversClass(GoodsReceiptCoordinator::class)]
final class GoodsReceiptCoordinatorTest extends TestCase
{
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

        $coordinator = new GoodsReceiptCoordinator(
            contextProvider: new class () {
                public function getPreReceiptContext(string $tenantId, string $purchaseOrderId, string $warehouseId, array $lineItems): never
                {
                    throw new \RuntimeException('Should not fetch pre-receipt context when PO is missing.');
                }

                public function getOutstandingQuantities(string $tenantId, string $purchaseOrderId): array
                {
                    return [];
                }

                public function getContext(string $tenantId, string $goodsReceiptId): never
                {
                    throw new \RuntimeException('Not used in this test.');
                }
            },
            ruleRegistry: new class () {
                public function validateOrFail(object $context): void
                {
                }
            },
            accrualService: new class () {
                public function postGoodsReceiptAccrual(
                    string $tenantId,
                    string $goodsReceiptId,
                    string $purchaseOrderId,
                    array $lineItems,
                    string $receivedBy
                ): string {
                    return 'accrual-1';
                }
            },
            purchaseOrderQuery: new class () {
                public ?array $lastFindArgs = null;

                public function findById(string $tenantId, string $id): ?object
                {
                    $this->lastFindArgs = [$tenantId, $id];
                    return null;
                }
            },
            goodsReceiptPersist: new class () {
                public bool $createCalled = false;

                public function create(array $data): string
                {
                    $this->createCalled = true;
                    return 'gr-1';
                }

                public function reverse(string $goodsReceiptId, string $reversedBy, string $reason): void
                {
                }
            },
            logger: new NullLogger(),
        );

        $result = $coordinator->record($request);

        $this->assertFalse($result->success);
        $this->assertSame('Purchase order not found: po-123', $result->message);
    }
}
