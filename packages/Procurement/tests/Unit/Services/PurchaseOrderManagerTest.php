<?php

declare(strict_types=1);

namespace Nexus\Procurement\Tests\Unit\Services;

use Nexus\Procurement\Contracts\PurchaseOrderInterface;
use Nexus\Procurement\Contracts\PurchaseOrderQueryInterface;
use Nexus\Procurement\Contracts\PurchaseOrderPersistInterface;
use Nexus\Procurement\Contracts\RequisitionInterface;
use Nexus\Procurement\Contracts\RequisitionRepositoryInterface;
use Nexus\Procurement\Exceptions\BudgetExceededException;
use Nexus\Procurement\Exceptions\InvalidPurchaseOrderDataException;
use Nexus\Procurement\Exceptions\InvalidRequisitionStateException;
use Nexus\Procurement\Exceptions\PurchaseOrderNotFoundException;
use Nexus\Procurement\Services\PurchaseOrderManager;
use Nexus\Procurement\Services\RequisitionManager;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class PurchaseOrderManagerTest extends TestCase
{
    private PurchaseOrderQueryInterface $poQuery;
    private PurchaseOrderPersistInterface $poPersist;
    private RequisitionRepositoryInterface $reqRepository;
    private RequisitionManager $reqManager;
    private PurchaseOrderManager $manager;

    protected function setUp(): void
    {
        $this->poQuery = $this->createMock(PurchaseOrderQueryInterface::class);
        $this->poPersist = $this->createMock(PurchaseOrderPersistInterface::class);
        $this->reqRepository = $this->createMock(RequisitionRepositoryInterface::class);
        $this->reqManager = new RequisitionManager(
            $this->reqRepository,
            $this->createMock(LoggerInterface::class)
        );
        $this->manager = new PurchaseOrderManager(
            $this->poQuery,
            $this->poPersist,
            $this->reqRepository,
            $this->reqManager,
            $this->createMock(LoggerInterface::class),
            10.0
        );
    }

    public function test_create_blanket_po_delegates_to_repository(): void
    {
        $po = $this->createMockPo('po-1', 'PO-BLANKET-001');

        $this->poPersist->expects(self::once())
            ->method('createBlanket')
            ->with('tenant-1', 'creator-1', self::callback(static fn(array $d) => isset($d['number'], $d['vendor_id'], $d['total_committed_value'], $d['valid_from'], $d['valid_until'], $d['description'])))
            ->willReturn($po);

        $result = $this->manager->createBlanketPo('tenant-1', 'creator-1', [
            'number' => 'PO-BLANKET-001',
            'vendor_id' => 'vendor-1',
            'total_committed_value' => 10000.0,
            'valid_from' => '2025-01-01',
            'valid_until' => '2025-12-31',
            'description' => 'Blanket PO',
        ]);

        self::assertSame($po, $result);
    }

    public function test_create_blanket_po_throws_when_missing_vendor(): void
    {
        $this->expectException(InvalidPurchaseOrderDataException::class);

        $this->manager->createBlanketPo('tenant-1', 'creator-1', [
            'number' => 'PO-001',
            'total_committed_value' => 1000,
            'valid_from' => '2025-01-01',
            'valid_until' => '2025-12-31',
            'description' => 'Test',
        ]);
    }

    public function test_approve_po_delegates_to_repository(): void
    {
        $po = $this->createMockPo('po-1', 'PO-001');
        $approved = $this->createMockPo('po-1', 'PO-001');

        $this->poQuery->method('findById')->with('tenant-1', 'po-1')->willReturn($po);
        $this->poPersist->expects(self::once())
            ->method('approve')
            ->with('po-1', 'approver-1', 'tenant-1')
            ->willReturn($approved);

        $result = $this->manager->approvePo('tenant-1', 'po-1', 'approver-1');

        self::assertSame($approved, $result);
    }

    public function test_approve_po_throws_when_not_found(): void
    {
        $this->poQuery->method('findById')->with('tenant-1', 'po-x')->willReturn(null);

        $this->expectException(PurchaseOrderNotFoundException::class);

        $this->manager->approvePo('tenant-1', 'po-x', 'approver-1');
    }

    public function test_close_po_delegates_to_repository(): void
    {
        $po = $this->createMockPo('po-1', 'PO-001');
        $closed = $this->createMockPo('po-1', 'PO-001');

        $this->poQuery->method('findById')->with('tenant-1', 'po-1')->willReturn($po);
        $this->poPersist->expects(self::once())
            ->method('updateStatus')
            ->with('po-1', 'closed', 'tenant-1')
            ->willReturn($closed);

        $result = $this->manager->closePo('tenant-1', 'po-1');

        self::assertSame($closed, $result);
    }

    public function test_get_purchase_order_delegates(): void
    {
        $po = $this->createMockPo('po-1', 'PO-001');

        $this->poQuery->expects(self::once())
            ->method('findById')
            ->with('tenant-1', 'po-1')
            ->willReturn($po);

        $result = $this->manager->getPurchaseOrder('tenant-1', 'po-1');

        self::assertSame($po, $result);
    }

    public function test_get_purchase_order_throws_when_not_found(): void
    {
        $this->poQuery->method('findById')->with('tenant-1', 'po-x')->willReturn(null);

        $this->expectException(PurchaseOrderNotFoundException::class);

        $this->manager->getPurchaseOrder('tenant-1', 'po-x');
    }

    public function test_get_purchase_orders_for_tenant_delegates(): void
    {
        $pos = [$this->createMockPo('po-1', 'PO-001')];

        $this->poQuery->expects(self::once())
            ->method('findByTenantId')
            ->with('tenant-1', [])
            ->willReturn($pos);

        $result = $this->manager->getPurchaseOrdersForTenant('tenant-1');

        self::assertSame($pos, $result);
    }

    public function test_get_purchase_orders_by_vendor_delegates(): void
    {
        $pos = [$this->createMockPo('po-1', 'PO-001')];

        $this->poQuery->expects(self::once())
            ->method('findByVendorId')
            ->with('tenant-1', 'vendor-1')
            ->willReturn($pos);

        $result = $this->manager->getPurchaseOrdersByVendor('tenant-1', 'vendor-1');

        self::assertSame($pos, $result);
    }

    public function test_create_from_requisition_throws_when_requisition_not_approved(): void
    {
        $req = $this->createMockRequisitionWithLines('req-1', 'REQ-001', 'draft');
        $this->reqRepository->method('findById')->with('req-1')->willReturn($req);

        $this->expectException(InvalidRequisitionStateException::class);

        $this->manager->createFromRequisition('tenant-1', 'req-1', 'creator-1', [
            'number' => 'PO-001',
            'vendor_id' => 'vendor-1',
            'lines' => [['requisition_line_id' => 'L1', 'quantity' => 10, 'unit_price' => 10, 'unit' => 'EA', 'item_code' => 'A', 'description' => 'Item A']],
        ]);
    }

    public function test_create_blanket_release_throws_when_exceeds_remaining(): void
    {
        $blanket = $this->createMockPoWithTotals('po-1', 'PO-BLANKET', 100.0, 90.0);

        $this->poQuery->method('findById')->with('tenant-1', 'po-1')->willReturn($blanket);

        $this->expectException(BudgetExceededException::class);

        $this->manager->createBlanketRelease('tenant-1', 'po-1', 'creator-1', [
            'release_number' => 'REL-001',
            'lines' => [['item_code' => 'A', 'description' => 'A', 'quantity' => 20, 'unit' => 'EA', 'unit_price' => 5]],
        ]);
    }

    private function createMockPo(string $id, string $number): PurchaseOrderInterface
    {
        $po = $this->createMock(PurchaseOrderInterface::class);
        $po->method('getId')->willReturn($id);
        $po->method('getPoNumber')->willReturn($number);
        $po->method('getCreatorId')->willReturn('creator-1');
        $po->method('getStatus')->willReturn('draft');

        return $po;
    }

    private function createMockPoWithTotals(string $id, string $number, float $committed, float $released): PurchaseOrderInterface
    {
        $po = $this->createMock(PurchaseOrderInterface::class);
        $po->method('getId')->willReturn($id);
        $po->method('getPoNumber')->willReturn($number);
        $po->method('getTotalCommittedValue')->willReturn($committed);
        $po->method('getTotalReleasedValue')->willReturn($released);

        return $po;
    }

    private function createMockRequisitionWithLines(string $id, string $number, string $status): RequisitionInterface
    {
        $line = $this->createMock(\Nexus\Procurement\Contracts\RequisitionLineInterface::class);
        $line->method('getLineNumber')->willReturn(1);
        $line->method('getItemCode')->willReturn('A');
        $line->method('getItemDescription')->willReturn('Item A');
        $line->method('getQuantity')->willReturn(10.0);
        $line->method('getUom')->willReturn('EA');
        $line->method('getUnitPriceEstimate')->willReturn(10.0);

        $req = $this->createMock(RequisitionInterface::class);
        $req->method('getId')->willReturn($id);
        $req->method('getRequisitionNumber')->willReturn($number);
        $req->method('getStatus')->willReturn($status);
        $req->method('getLines')->willReturn([$line]);
        $req->method('getTotalEstimate')->willReturn(100.0);
        $req->method('isConverted')->willReturn(false);

        return $req;
    }
}
