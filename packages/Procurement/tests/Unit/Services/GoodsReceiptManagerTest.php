<?php

declare(strict_types=1);

namespace Nexus\Procurement\Tests\Unit\Services;

use Nexus\Procurement\Contracts\GoodsReceiptNoteInterface;
use Nexus\Procurement\Contracts\GoodsReceiptRepositoryInterface;
use Nexus\Procurement\Contracts\PurchaseOrderInterface;
use Nexus\Procurement\Contracts\PurchaseOrderLineInterface;
use Nexus\Procurement\Contracts\PurchaseOrderQueryInterface;
use Nexus\Procurement\Exceptions\GoodsReceiptNotFoundException;
use Nexus\Procurement\Exceptions\InvalidGoodsReceiptDataException;
use Nexus\Procurement\Exceptions\PurchaseOrderNotFoundException;
use Nexus\Procurement\Exceptions\UnauthorizedApprovalException;
use Nexus\Procurement\Services\GoodsReceiptManager;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class GoodsReceiptManagerTest extends TestCase
{
    private GoodsReceiptRepositoryInterface $repository;
    private PurchaseOrderQueryInterface $poRepository;
    private GoodsReceiptManager $manager;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(GoodsReceiptRepositoryInterface::class);
        $this->poRepository = $this->createMock(PurchaseOrderQueryInterface::class);
        $this->manager = new GoodsReceiptManager(
            $this->repository,
            $this->poRepository,
            $this->createMock(LoggerInterface::class)
        );
    }

    public function test_create_goods_receipt_delegates_to_repository(): void
    {
        $po = $this->createMockPoWithLines('po-1', 'PO-001', 'creator-1', [['ref' => 'PO-001-L1', 'qty' => 10]]);
        $grn = $this->createMockGrn('grn-1', 'GRN-001');

        $this->poRepository->method('findById')->with('po-1')->willReturn($po);
        $this->repository->expects(self::once())
            ->method('create')
            ->with('tenant-1', 'po-1', 'receiver-1', self::callback(static fn(array $d) => isset($d['number'], $d['received_date'], $d['lines'])))
            ->willReturn($grn);

        $result = $this->manager->createGoodsReceipt('tenant-1', 'po-1', 'receiver-1', [
            'number' => 'GRN-001',
            'received_date' => '2025-01-01',
            'lines' => [['po_line_reference' => 'PO-001-L1', 'quantity_received' => 5, 'unit' => 'EA']],
        ]);

        self::assertSame($grn, $result);
    }

    public function test_create_goods_receipt_throws_when_po_not_found(): void
    {
        $this->poRepository->method('findById')->with('po-x')->willReturn(null);

        $this->expectException(PurchaseOrderNotFoundException::class);

        $this->manager->createGoodsReceipt('tenant-1', 'po-x', 'receiver-1', [
            'number' => 'GRN-001',
            'received_date' => '2025-01-01',
            'lines' => [['po_line_reference' => 'L1', 'quantity_received' => 5, 'unit' => 'EA']],
        ]);
    }

    public function test_create_goods_receipt_throws_when_po_creator_is_receiver(): void
    {
        $po = $this->createMockPoWithLines('po-1', 'PO-001', 'creator-1', [['ref' => 'PO-001-L1', 'qty' => 10]]);

        $this->poRepository->method('findById')->with('po-1')->willReturn($po);

        $this->expectException(UnauthorizedApprovalException::class);

        $this->manager->createGoodsReceipt('tenant-1', 'po-1', 'creator-1', [
            'number' => 'GRN-001',
            'received_date' => '2025-01-01',
            'lines' => [['po_line_reference' => 'PO-001-L1', 'quantity_received' => 5, 'unit' => 'EA']],
        ]);
    }

    public function test_create_goods_receipt_throws_when_no_lines(): void
    {
        $this->expectException(InvalidGoodsReceiptDataException::class);

        $this->manager->createGoodsReceipt('tenant-1', 'po-1', 'receiver-1', [
            'number' => 'GRN-001',
            'received_date' => '2025-01-01',
            'lines' => [],
        ]);
    }

    public function test_authorize_payment_delegates_to_repository(): void
    {
        $grn = $this->createMockGrnWithReceiver('grn-1', 'GRN-001', 'receiver-1');
        $authorized = $this->createMockGrn('grn-1', 'GRN-001');

        $this->repository->method('findById')->with('grn-1')->willReturn($grn);
        $this->repository->expects(self::once())
            ->method('authorizePayment')
            ->with('tenant-1', 'grn-1', 'authorizer-2')
            ->willReturn($authorized);

        $result = $this->manager->authorizePayment('tenant-1', 'grn-1', 'authorizer-2');

        self::assertSame($authorized, $result);
    }

    public function test_authorize_payment_throws_when_receiver_is_authorizer(): void
    {
        $grn = $this->createMockGrnWithReceiver('grn-1', 'GRN-001', 'receiver-1');

        $this->repository->method('findById')->with('grn-1')->willReturn($grn);

        $this->expectException(UnauthorizedApprovalException::class);

        $this->manager->authorizePayment('tenant-1', 'grn-1', 'receiver-1');
    }

    public function test_authorize_payment_throws_when_not_found(): void
    {
        $this->repository->method('findById')->with('grn-x')->willReturn(null);

        $this->expectException(GoodsReceiptNotFoundException::class);

        $this->manager->authorizePayment('tenant-1', 'grn-x', 'authorizer-1');
    }

    public function test_get_goods_receipt_delegates(): void
    {
        $grn = $this->createMockGrn('grn-1', 'GRN-001');

        $this->repository->expects(self::once())
            ->method('findById')
            ->with('grn-1')
            ->willReturn($grn);

        $result = $this->manager->getGoodsReceipt('grn-1');

        self::assertSame($grn, $result);
    }

    public function test_get_goods_receipt_throws_when_not_found(): void
    {
        $this->repository->method('findById')->with('grn-x')->willReturn(null);

        $this->expectException(GoodsReceiptNotFoundException::class);

        $this->manager->getGoodsReceipt('grn-x');
    }

    public function test_get_goods_receipts_for_tenant_delegates(): void
    {
        $grns = [$this->createMockGrn('grn-1', 'GRN-001')];

        $this->repository->expects(self::once())
            ->method('findByTenantId')
            ->with('tenant-1', [])
            ->willReturn($grns);

        $result = $this->manager->getGoodsReceiptsForTenant('tenant-1');

        self::assertSame($grns, $result);
    }

    public function test_get_goods_receipts_by_po_delegates(): void
    {
        $grns = [$this->createMockGrn('grn-1', 'GRN-001')];

        $this->repository->expects(self::once())
            ->method('findByPurchaseOrder')
            ->with('po-1')
            ->willReturn($grns);

        $result = $this->manager->getGoodsReceiptsByPo('po-1');

        self::assertSame($grns, $result);
    }

    private function createMockPoWithLines(string $id, string $number, string $creatorId, array $lines): PurchaseOrderInterface
    {
        $poLines = [];
        foreach ($lines as $l) {
            $line = $this->createMock(PurchaseOrderLineInterface::class);
            $line->method('getLineReference')->willReturn($l['ref']);
            $line->method('getQuantity')->willReturn((float)$l['qty']);
            $poLines[] = $line;
        }

        $po = $this->createMock(PurchaseOrderInterface::class);
        $po->method('getId')->willReturn($id);
        $po->method('getPoNumber')->willReturn($number);
        $po->method('getCreatorId')->willReturn($creatorId);
        $po->method('getLines')->willReturn($poLines);

        return $po;
    }

    private function createMockGrn(string $id, string $number): GoodsReceiptNoteInterface
    {
        $grn = $this->createMock(GoodsReceiptNoteInterface::class);
        $grn->method('getId')->willReturn($id);
        $grn->method('getGrnNumber')->willReturn($number);
        $grn->method('getStatus')->willReturn('draft');

        return $grn;
    }

    private function createMockGrnWithReceiver(string $id, string $number, string $receivedBy): GoodsReceiptNoteInterface
    {
        $grn = $this->createMock(GoodsReceiptNoteInterface::class);
        $grn->method('getId')->willReturn($id);
        $grn->method('getGrnNumber')->willReturn($number);
        $grn->method('getReceivedBy')->willReturn($receivedBy);
        $grn->method('getStatus')->willReturn('draft');

        return $grn;
    }
}
