<?php

declare(strict_types=1);

namespace Nexus\SupplyChainOperations\Tests\Unit\Workflows;

use Nexus\Sales\Contracts\SalesOrderRepositoryInterface;
use Nexus\Warehouse\Contracts\WarehouseManagerInterface;
use Nexus\Inventory\Contracts\StockManagerInterface;
use Nexus\QualityControl\Contracts\InspectionManagerInterface;
use Nexus\Receivable\Contracts\ReceivableManagerInterface;
use Nexus\SupplyChainOperations\Workflows\Rma\RmaWorkflow;
use Nexus\SupplyChainOperations\Workflows\Rma\RmaRequest;
use Nexus\SupplyChainOperations\Workflows\Rma\RmaStatus;
use Nexus\AuditLogger\Services\AuditLogManager;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class RmaWorkflowTest extends TestCase
{
    private SalesOrderRepositoryInterface $salesOrderRepository;
    private WarehouseManagerInterface $warehouseManager;
    private StockManagerInterface $stockManager;
    private InspectionManagerInterface $inspectionManager;
    private ReceivableManagerInterface $receivableManager;
    private AuditLogManager $auditLogger;
    private LoggerInterface $logger;
    private RmaWorkflow $workflow;

    protected function setUp(): void
    {
        $this->salesOrderRepository = $this->createMock(SalesOrderRepositoryInterface::class);
        $this->warehouseManager = $this->createMock(WarehouseManagerInterface::class);
        $this->stockManager = $this->createMock(StockManagerInterface::class);
        $this->inspectionManager = $this->createMock(InspectionManagerInterface::class);
        $this->receivableManager = $this->createMock(ReceivableManagerInterface::class);
        $this->auditLogger = $this->createMock(AuditLogManager::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->workflow = new RmaWorkflow(
            $this->salesOrderRepository,
            $this->warehouseManager,
            $this->stockManager,
            $this->inspectionManager,
            $this->receivableManager,
            $this->auditLogger,
            $this->logger
        );
    }

    public function test_initiate_return_creates_rma_with_pending_receipt_status(): void
    {
        $request = new RmaRequest(
            tenantId: 'tenant-001',
            salesOrderId: 'so-001',
            customerId: 'customer-001',
            items: [
                ['product_id' => 'product-001', 'quantity' => 2.0]
            ],
            reason: 'defective_product'
        );

        $this->auditLogger
            ->expects($this->once())
            ->method('log')
            ->with(
                'rma_initiated',
                $this->stringContains('RMA'),
                $this->anything()
            );

        $result = $this->workflow->initiateReturn($request);

        $this->assertStringStartsWith('RMA-', $result->rmaId);
        $this->assertSame('so-001', $result->salesOrderId);
        $this->assertSame(RmaStatus::PENDING_RECEIPT, $result->status);
    }

    public function test_receive_return_updates_status_to_pending_inspection(): void
    {
        $rmaResult = $this->createRmaResult(RmaStatus::PENDING_RECEIPT);

        $this->stockManager
            ->expects($this->once())
            ->method('receiveReturn');

        $this->auditLogger
            ->expects($this->once())
            ->method('log')
            ->with('rma_received');

        $result = $this->workflow->receiveReturn($rmaResult, 'warehouse-001');

        $this->assertSame(RmaStatus::PENDING_INSPECTION, $result->status);
    }

    public function test_process_inspection_creates_credit_note_and_restock(): void
    {
        $rmaResult = $this->createRmaResult(RmaStatus::PENDING_INSPECTION);

        $inspectionResults = [
            [
                'product_id' => 'product-001',
                'warehouse_id' => 'warehouse-001',
                'quantity' => 2.0,
                'condition' => 'resellable',
                'unit_price' => 50.0
            ]
        ];

        $this->stockManager
            ->expects($this->once())
            ->method('adjustStock');

        $this->receivableManager
            ->expects($this->once())
            ->method('createCreditNote');

        $this->auditLogger
            ->expects($this->once())
            ->method('log')
            ->with('rma_processed');

        $result = $this->workflow->processInspection($rmaResult, $inspectionResults);

        $this->assertSame(RmaStatus::COMPLETED, $result->status);
        $this->assertSame(100.0, $result->metadata['credit_amount']);
    }

    public function test_process_inspection_handles_scrap_items(): void
    {
        $rmaResult = $this->createRmaResult(RmaStatus::PENDING_INSPECTION);

        $inspectionResults = [
            [
                'product_id' => 'product-001',
                'warehouse_id' => 'warehouse-001',
                'quantity' => 1.0,
                'condition' => 'damaged',
                'unit_price' => 50.0
            ]
        ];

        $this->stockManager
            ->expects($this->once())
            ->method('writeOff');

        $this->receivableManager
            ->expects($this->once())
            ->method('createCreditNote');

        $result = $this->workflow->processInspection($rmaResult, $inspectionResults);

        $this->assertSame(RmaStatus::COMPLETED, $result->status);
        $this->assertEmpty($result->metadata['restock_items']);
        $this->assertNotEmpty($result->metadata['scrap_items']);
    }

    private function createRmaResult(RmaStatus $status): \Nexus\SupplyChainOperations\Workflows\Rma\RmaResult
    {
        return new \Nexus\SupplyChainOperations\Workflows\Rma\RmaResult(
            rmaId: 'RMA-test-001',
            salesOrderId: 'so-001',
            status: $status,
            items: [
                ['product_id' => 'product-001', 'quantity' => 2.0, 'tenant_id' => 'tenant-001', 'customer_id' => 'customer-001']
            ],
            tenantId: 'tenant-001',
            customerId: 'customer-001'
        );
    }
}
