<?php

declare(strict_types=1);

namespace Nexus\SupplyChainOperations\Tests\Unit\Coordinators;

use Nexus\Inventory\Contracts\StockManagerInterface;
use Nexus\Procurement\Contracts\GoodsReceiptRepositoryInterface;
use Nexus\Procurement\Contracts\PurchaseOrderRepositoryInterface;
use Nexus\Procurement\Contracts\GoodsReceiptNoteInterface;
use Nexus\Procurement\Contracts\GoodsReceiptLineInterface;
use Nexus\Procurement\Contracts\PurchaseOrderLineInterface;
use Nexus\SupplyChainOperations\Coordinators\LandedCostCoordinator;
use Nexus\AuditLogger\Services\AuditLogManager;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class LandedCostCoordinatorTest extends TestCase
{
    private StockManagerInterface $stockManager;
    private GoodsReceiptRepositoryInterface $grnRepository;
    private PurchaseOrderRepositoryInterface $purchaseOrderRepository;
    private AuditLogManager $auditLogger;
    private LoggerInterface $logger;
    private LandedCostCoordinator $coordinator;

    protected function setUp(): void
    {
        $this->stockManager = $this->createMock(StockManagerInterface::class);
        $this->grnRepository = $this->createMock(GoodsReceiptRepositoryInterface::class);
        $this->purchaseOrderRepository = $this->createMock(PurchaseOrderRepositoryInterface::class);
        $this->auditLogger = $this->createMock(AuditLogManager::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->coordinator = new LandedCostCoordinator(
            $this->stockManager,
            $this->grnRepository,
            $this->purchaseOrderRepository,
            $this->auditLogger,
            $this->logger
        );
    }

    public function test_distribute_landed_cost_by_value(): void
    {
        $grnId = 'GRN-001';
        $amount = 100.0;

        $poLine = $this->createMock(PurchaseOrderLineInterface::class);
        $poLine->method('getProductVariantId')->willReturn('product-001');
        $poLine->method('getUnitPrice')->willReturn(10.0);

        $grnLine = $this->createMock(GoodsReceiptLineInterface::class);
        $grnLine->method('getQuantity')->willReturn(5.0);
        $grnLine->method('getPoLineReference')->willReturn('PO-LINE-001');

        $grn = $this->createMock(GoodsReceiptNoteInterface::class);
        $grn->method('getId')->willReturn($grnId);
        $grn->method('getLines')->willReturn([$grnLine]);

        $this->grnRepository
            ->expects($this->once())
            ->method('findById')
            ->with($grnId)
            ->willReturn($grn);

        $this->purchaseOrderRepository
            ->expects($this->once())
            ->method('findLineByReference')
            ->with('PO-LINE-001')
            ->willReturn($poLine);

        $this->stockManager
            ->expects($this->once())
            ->method('capitalizeLandedCost')
            ->with('product-001', 100.0);

        $this->auditLogger
            ->expects($this->once())
            ->method('log')
            ->with(
                'supply_chain_landed_cost_capitalized',
                $this->stringContains('Capitalized'),
                $this->anything()
            );

        $this->coordinator->distributeLandedCost($grnId, $amount, 'value');
    }

    public function test_distribute_landed_cost_by_quantity(): void
    {
        $grnId = 'GRN-002';
        $amount = 50.0;

        $grnLine = $this->createMock(GoodsReceiptLineInterface::class);
        $grnLine->method('getQuantity')->willReturn(10.0);
        $grnLine->method('getPoLineReference')->willReturn('PO-LINE-002');

        $grn = $this->createMock(GoodsReceiptNoteInterface::class);
        $grn->method('getId')->willReturn($grnId);
        $grn->method('getLines')->willReturn([$grnLine]);

        $this->grnRepository
            ->expects($this->once())
            ->method('findById')
            ->with($grnId)
            ->willReturn($grn);

        $this->purchaseOrderRepository
            ->expects($this->never())
            ->method('findLineByReference');

        $this->stockManager
            ->expects($this->once())
            ->method('capitalizeLandedCost');

        $this->coordinator->distributeLandedCost($grnId, $amount, 'quantity');
    }

    public function test_distribute_landed_cost_grn_not_found(): void
    {
        $grnId = 'GRN-INVALID';

        $this->grnRepository
            ->expects($this->once())
            ->method('findById')
            ->with($grnId)
            ->willReturn(null);

        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with($this->stringContains('not found'));

        $this->stockManager
            ->expects($this->never())
            ->method('capitalizeLandedCost');

        $this->coordinator->distributeLandedCost($grnId, 100.0);
    }
}
