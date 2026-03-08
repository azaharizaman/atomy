<?php

declare(strict_types=1);

namespace Nexus\Procurement\Tests\Unit\Services;

use Nexus\Procurement\Contracts\GoodsReceiptLineInterface;
use Nexus\Procurement\Contracts\GoodsReceiptNoteInterface;
use Nexus\Procurement\Contracts\GoodsReceiptRepositoryInterface;
use Nexus\Procurement\Contracts\PurchaseOrderInterface;
use Nexus\Procurement\Contracts\PurchaseOrderLineInterface;
use Nexus\Procurement\Contracts\PurchaseOrderQueryInterface;
use Nexus\Procurement\Contracts\PurchaseOrderPersistInterface;
use Nexus\Procurement\Contracts\PurchaseOrderRepositoryInterface;
use Nexus\Procurement\Contracts\DatabaseTransactionInterface;
use Nexus\Procurement\Contracts\RequisitionInterface;
use Nexus\Procurement\Contracts\RequisitionRepositoryInterface;
use Nexus\Procurement\Contracts\VendorQuoteInterface;
use Nexus\Procurement\Contracts\VendorQuoteRepositoryInterface;
use Nexus\Procurement\Services\GoodsReceiptManager;
use Nexus\Procurement\Services\MatchingEngine;
use Nexus\Procurement\Services\ProcurementManager;
use Nexus\Procurement\Services\PurchaseOrderManager;
use Nexus\Procurement\Services\RequisitionManager;
use Nexus\Procurement\Services\VendorQuoteManager;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class ProcurementManagerTest extends TestCase
{
    private RequisitionRepositoryInterface $reqRepo;
    private PurchaseOrderQueryInterface $poQuery;
    private PurchaseOrderPersistInterface $poPersist;
    private GoodsReceiptRepositoryInterface $grnRepo;
    private VendorQuoteRepositoryInterface $quoteRepo;
    private DatabaseTransactionInterface $transaction;
    private LoggerInterface $logger;
    private RequisitionManager $requisitionManager;
    private PurchaseOrderManager $purchaseOrderManager;
    private GoodsReceiptManager $goodsReceiptManager;
    private VendorQuoteManager $vendorQuoteManager;
    private MatchingEngine $matchingEngine;
    private ProcurementManager $manager;

    protected function setUp(): void
    {
        $this->reqRepo = $this->createMock(RequisitionRepositoryInterface::class);
        $this->poQuery = $this->createMock(PurchaseOrderQueryInterface::class);
        $this->poPersist = $this->createMock(PurchaseOrderPersistInterface::class);
        $this->grnRepo = $this->createMock(GoodsReceiptRepositoryInterface::class);
        $this->quoteRepo = $this->createMock(VendorQuoteRepositoryInterface::class);
        $this->transaction = $this->createMock(DatabaseTransactionInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->reqRepo->method('generateNextNumber')->willReturn('REQ-001');
        $this->reqRepo->method('findByTenantId')->willReturn([]);
        $req = $this->createMock(RequisitionInterface::class);
        $req->method('getId')->willReturn('req-1');
        $req->method('getRequisitionNumber')->willReturn('REQ-001');
        $this->reqRepo->method('create')->willReturn($req);

        $quote = $this->createMock(VendorQuoteInterface::class);
        $quote->method('getId')->willReturn('quote-1');
        $this->quoteRepo->method('create')->willReturn($quote);
        $this->quoteRepo->method('findByRequisitionId')->willReturn([]);

        $this->poQuery->method('findByTenantId')->willReturn([]);
        $this->grnRepo->method('findByTenantId')->willReturn([]);

        // Default transaction behavior
        $this->transaction->method('transactional')->willReturnCallback(fn($cb) => $cb());

        $this->requisitionManager = new RequisitionManager($this->reqRepo, $this->logger);
        $this->purchaseOrderManager = new PurchaseOrderManager(
            $this->poQuery,
            $this->poPersist,
            $this->reqRepo,
            $this->requisitionManager,
            $this->transaction,
            $this->logger,
            10.0
        );
        $this->goodsReceiptManager = new GoodsReceiptManager($this->grnRepo, $this->poQuery, $this->logger);
        $this->vendorQuoteManager = new VendorQuoteManager($this->quoteRepo, $this->logger);
        $this->matchingEngine = new MatchingEngine($this->logger);

        $this->manager = new ProcurementManager(
            $this->requisitionManager,
            $this->purchaseOrderManager,
            $this->goodsReceiptManager,
            $this->vendorQuoteManager,
            $this->matchingEngine,
            $this->logger
        );
    }

    public function test_create_requisition_delegates(): void
    {
        $result = $this->manager->createRequisition('tenant-1', 'user-1', [
            'number' => 'REQ-001',
            'description' => 'Test',
            'department' => 'IT',
            'lines' => [['item_code' => 'A', 'description' => 'A', 'quantity' => 1, 'unit' => 'EA', 'estimated_unit_price' => 10]],
        ]);

        self::assertInstanceOf(RequisitionInterface::class, $result);
    }

    public function test_perform_three_way_match_delegates(): void
    {
        $poLine = $this->createMock(PurchaseOrderLineInterface::class);
        $poLine->method('getLineReference')->willReturn('PO-001-L1');
        $poLine->method('getQuantity')->willReturn(10.0);
        $poLine->method('getUnitPrice')->willReturn(25.0);
        $grnLine = $this->createMock(GoodsReceiptLineInterface::class);
        $grnLine->method('getPoLineReference')->willReturn('PO-001-L1');
        $grnLine->method('getQuantity')->willReturn(10.0);

        $result = $this->manager->performThreeWayMatch($poLine, $grnLine, [
            'quantity' => 10.0,
            'unit_price' => 25.0,
            'line_total' => 250.0,
        ]);

        self::assertIsArray($result);
        self::assertArrayHasKey('matched', $result);
        self::assertArrayHasKey('discrepancies', $result);
        self::assertArrayHasKey('recommendation', $result);
    }

    public function test_get_requisitions_for_tenant_delegates(): void
    {
        $result = $this->manager->getRequisitionsForTenant('tenant-1');

        self::assertIsArray($result);
    }

    public function test_get_purchase_orders_for_tenant_delegates(): void
    {
        $result = $this->manager->getPurchaseOrdersForTenant('tenant-1');

        self::assertIsArray($result);
    }

    public function test_get_goods_receipts_for_tenant_delegates(): void
    {
        $result = $this->manager->getGoodsReceiptsForTenant('tenant-1');

        self::assertIsArray($result);
    }

    public function test_create_vendor_quote_delegates(): void
    {
        $result = $this->manager->createVendorQuote('tenant-1', 'req-1', [
            'rfq_number' => 'RFQ-001',
            'vendor_id' => 'v1',
            'quote_reference' => 'VQ-001',
            'quoted_date' => '2025-01-01',
            'valid_until' => '2025-02-01',
            'lines' => [['item_code' => 'A', 'description' => 'A', 'quantity' => 1, 'unit' => 'EA', 'unit_price' => 10]],
        ]);

        self::assertInstanceOf(VendorQuoteInterface::class, $result);
    }

    public function test_compare_vendor_quotes_delegates(): void
    {
        $result = $this->manager->compareVendorQuotes('tenant-1', 'req-1');

        self::assertIsArray($result);
        self::assertSame('req-1', $result['requisition_id']);
        self::assertSame(0, $result['quote_count']);
    }

    public function test_submit_requisition_for_approval_delegates(): void
    {
        $req = $this->createMock(RequisitionInterface::class);
        $req->method('getId')->willReturn('req-1');
        $req->method('getRequisitionNumber')->willReturn('REQ-001');
        $req->method('getStatus')->willReturn('draft');
        $updated = $this->createMock(RequisitionInterface::class);
        $updated->method('getStatus')->willReturn('pending_approval');
        
        $this->reqRepo->method('findById')->with('tenant-1', 'req-1')->willReturn($req);
        $this->reqRepo->method('updateStatus')->with('tenant-1', 'req-1', 'pending_approval')->willReturn($updated);

        $result = $this->manager->submitRequisitionForApproval('tenant-1', 'req-1');

        self::assertInstanceOf(RequisitionInterface::class, $result);
    }

    public function test_release_po_delegates(): void
    {
        $po = $this->createMock(PurchaseOrderInterface::class);
        $po->method('getId')->willReturn('po-1');
        $po->method('getPoNumber')->willReturn('PO-001');
        
        $this->poQuery->method('findById')->with('tenant-1', 'po-1')->willReturn($po);

        $result = $this->manager->releasePO('tenant-1', 'po-1', 'user-1');

        self::assertInstanceOf(PurchaseOrderInterface::class, $result);
    }

    public function test_create_direct_po_delegates(): void
    {
        $po = $this->createMock(PurchaseOrderInterface::class);
        $po->method('getId')->willReturn('po-1');
        $po->method('getPoNumber')->willReturn('PO-DIRECT-001');
        
        $this->poPersist->method('createBlanket')->willReturn($po);

        $result = $this->manager->createDirectPO('tenant-1', 'creator-1', [
            'number' => 'PO-DIRECT-001',
            'vendor_id' => 'vendor-1',
            'total_committed_value' => 5000,
            'valid_from' => '2025-01-01',
            'valid_until' => '2025-12-31',
            'description' => 'Direct PO',
        ]);

        self::assertInstanceOf(PurchaseOrderInterface::class, $result);
    }

    public function test_get_requisition_delegates(): void
    {
        $req = $this->createMock(RequisitionInterface::class);
        $this->reqRepo->method('findById')->with('tenant-1', 'req-1')->willReturn($req);

        $result = $this->manager->getRequisition('tenant-1', 'req-1');

        self::assertInstanceOf(RequisitionInterface::class, $result);
    }

    public function test_get_purchase_order_delegates(): void
    {
        $po = $this->createMock(PurchaseOrderInterface::class);
        $this->poQuery->method('findById')->with('tenant-1', 'po-1')->willReturn($po);

        $result = $this->manager->getPurchaseOrder('tenant-1', 'po-1');

        self::assertInstanceOf(PurchaseOrderInterface::class, $result);
    }

    public function test_get_goods_receipt_delegates(): void
    {
        $grn = $this->createMock(GoodsReceiptNoteInterface::class);
        $this->grnRepo->method('findByTenantAndId')->with('tenant-1', 'grn-1')->willReturn($grn);

        $result = $this->manager->getGoodsReceipt('tenant-1', 'grn-1');

        self::assertInstanceOf(GoodsReceiptNoteInterface::class, $result);
    }

    public function test_accept_vendor_quote_delegates(): void
    {
        $quote = $this->createMock(VendorQuoteInterface::class);
        $quote->method('getId')->willReturn('quote-1');
        $quote->method('getRfqNumber')->willReturn('RFQ-001');
        $quote->method('getStatus')->willReturn('accepted');
        
        $this->quoteRepo->method('findById')->with('tenant-1', 'quote-1')->willReturn($quote);
        $this->quoteRepo->method('accept')->with('tenant-1', 'quote-1', 'user-1')->willReturn($quote);

        $result = $this->manager->acceptVendorQuote('tenant-1', 'quote-1', 'user-1');

        self::assertInstanceOf(VendorQuoteInterface::class, $result);
    }

    public function test_approve_requisition_delegates(): void
    {
        $req = $this->createMock(RequisitionInterface::class);
        $req->method('getId')->willReturn('req-1');
        $req->method('getRequisitionNumber')->willReturn('REQ-001');
        $req->method('getRequesterId')->willReturn('user-1');
        $req->method('getStatus')->willReturn('pending_approval');
        $approved = $this->createMock(RequisitionInterface::class);
        $approved->method('getStatus')->willReturn('approved');
        
        $this->reqRepo->method('findById')->with('tenant-1', 'req-1')->willReturn($req);
        $this->reqRepo->method('approve')->with('tenant-1', 'req-1', 'approver-2')->willReturn($approved);

        $result = $this->manager->approveRequisition('tenant-1', 'req-1', 'approver-2');

        self::assertInstanceOf(RequisitionInterface::class, $result);
    }

    public function test_reject_requisition_delegates(): void
    {
        $req = $this->createMock(RequisitionInterface::class);
        $req->method('getRequisitionNumber')->willReturn('REQ-001');
        $req->method('getStatus')->willReturn('pending_approval');
        $rejected = $this->createMock(RequisitionInterface::class);
        
        $this->reqRepo->method('findById')->with('tenant-1', 'req-1')->willReturn($req);
        $this->reqRepo->method('reject')->with('tenant-1', 'req-1', 'rejector-1', 'Out of budget')->willReturn($rejected);

        $result = $this->manager->rejectRequisition('tenant-1', 'req-1', 'rejector-1', 'Out of budget');

        self::assertInstanceOf(RequisitionInterface::class, $result);
    }

    public function test_record_goods_receipt_delegates(): void
    {
        $po = $this->createMock(\Nexus\Procurement\Contracts\PurchaseOrderInterface::class);
        $po->method('getId')->willReturn('po-1');
        $po->method('getPoNumber')->willReturn('PO-001');
        $po->method('getCreatorId')->willReturn('creator-1');
        $poLine = $this->createMock(\Nexus\Procurement\Contracts\PurchaseOrderLineInterface::class);
        $poLine->method('getLineReference')->willReturn('PO-001-L1');
        $poLine->method('getQuantity')->willReturn(10.0);
        $po->method('getLines')->willReturn([$poLine]);
        $grn = $this->createMock(GoodsReceiptNoteInterface::class);
        $grn->method('getId')->willReturn('grn-1');
        $grn->method('getGrnNumber')->willReturn('GRN-001');
        $grn->method('getStatus')->willReturn('draft');
        
        $this->poQuery->method('findById')->willReturn($po);
        $this->grnRepo->method('create')->willReturn($grn);

        $result = $this->manager->recordGoodsReceipt('tenant-1', 'po-1', 'receiver-1', [
            'number' => 'GRN-001',
            'received_date' => '2025-01-01',
            'lines' => [['po_line_reference' => 'PO-001-L1', 'quantity_received' => 5, 'unit' => 'EA']],
        ]);

        self::assertInstanceOf(GoodsReceiptNoteInterface::class, $result);
    }

    public function test_authorize_grn_payment_delegates(): void
    {
        $grn = $this->createMock(GoodsReceiptNoteInterface::class);
        $grn->method('getId')->willReturn('grn-1');
        $grn->method('getGrnNumber')->willReturn('GRN-001');
        $grn->method('getReceivedBy')->willReturn('receiver-1');
        $grn->method('getStatus')->willReturn('authorized');
        
        $this->grnRepo->method('findByTenantAndId')->with('tenant-1', 'grn-1')->willReturn($grn);
        $this->grnRepo->method('authorizePayment')->with('tenant-1', 'grn-1', 'authorizer-1')->willReturn($grn);

        $result = $this->manager->authorizeGrnPayment('tenant-1', 'grn-1', 'authorizer-1');

        self::assertInstanceOf(GoodsReceiptNoteInterface::class, $result);
    }
}
