<?php

declare(strict_types=1);

namespace Nexus\SupplyChainOperations\Tests\Unit\Coordinators;

use Nexus\Procurement\Contracts\ProcurementManagerInterface;
use Nexus\Procurement\Contracts\PurchaseOrderInterface;
use Nexus\Sales\Contracts\SalesOrderInterface;
use Nexus\Sales\Contracts\SalesOrderLineInterface;
use Nexus\SupplyChainOperations\Coordinators\DropshipCoordinator;
use Nexus\AuditLogger\Services\AuditLogManager;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for DropshipCoordinator.
 *
 * @covers \Nexus\SupplyChainOperations\Coordinators\DropshipCoordinator
 */
final class DropshipCoordinatorTest extends TestCase
{
    private ProcurementManagerInterface $procurementManager;
    private AuditLogManager $auditLogger;
    private LoggerInterface $logger;
    private DropshipCoordinator $coordinator;

    protected function setUp(): void
    {
        $this->procurementManager = $this->createMock(ProcurementManagerInterface::class);
        $this->auditLogger = $this->createMock(AuditLogManager::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->coordinator = new DropshipCoordinator(
            $this->procurementManager,
            $this->auditLogger,
            $this->logger
        );
    }

    public function test_create_dropship_po_creates_po_with_correct_data(): void
    {
        // Arrange
        $salesOrder = $this->createMock(SalesOrderInterface::class);
        $salesOrder->method('getTenantId')->willReturn('tenant-001');
        $salesOrder->method('getCustomerId')->willReturn('customer-001');
        $salesOrder->method('getOrderNumber')->willReturn('SO-12345');
        $salesOrder->method('getId')->willReturn('so-001');
        $salesOrder->method('getCurrencyCode')->willReturn('USD');
        $salesOrder->method('getShippingAddress')->willReturn(['street' => '123 Main St']);
        $salesOrder->method('getConfirmedBy')->willReturn('user-001');

        $line = $this->createMock(SalesOrderLineInterface::class);
        $line->method('getProductVariantId')->willReturn('product-001');
        $line->method('getQuantity')->willReturn(5.0);
        $line->method('getId')->willReturn('line-001');

        $purchaseOrder = $this->createMock(PurchaseOrderInterface::class);
        $purchaseOrder->method('getId')->willReturn('po-001');

        $this->procurementManager
            ->expects($this->once())
            ->method('createDirectPO')
            ->with(
                'tenant-001',
                'user-001',
                $this->callback(function ($data) {
                    return $data['vendor_id'] === 'vendor-001'
                        && $data['type'] === 'DROPSHIP'
                        && $data['currency'] === 'USD'
                        && $data['shipping_address'] === ['street' => '123 Main St']
                        && $data['items'][0]['product_id'] === 'product-001'
                        && $data['items'][0]['quantity'] === 5.0
                        && $data['items'][0]['metadata']['is_dropship'] === true;
                })
            )
            ->willReturn($purchaseOrder);

        $this->auditLogger
            ->expects($this->once())
            ->method('log')
            ->with(
                'supply_chain_dropship_po_created',
                $this->stringContains('Dropship PO po-001 created'),
                $this->anything()
            );

        // Act
        $poId = $this->coordinator->createDropshipPo(
            $salesOrder,
            [$line],
            'vendor-001'
        );

        // Assert
        $this->assertSame('po-001', $poId);
    }

    public function test_create_dropship_po_uses_system_user_when_confirmed_by_null(): void
    {
        // Arrange
        $salesOrder = $this->createMock(SalesOrderInterface::class);
        $salesOrder->method('getTenantId')->willReturn('tenant-001');
        $salesOrder->method('getCustomerId')->willReturn('customer-001');
        $salesOrder->method('getOrderNumber')->willReturn('SO-12345');
        $salesOrder->method('getId')->willReturn('so-001');
        $salesOrder->method('getCurrencyCode')->willReturn('USD');
        $salesOrder->method('getShippingAddress')->willReturn([]);
        $salesOrder->method('getConfirmedBy')->willReturn(null);

        $line = $this->createMock(SalesOrderLineInterface::class);
        $line->method('getProductVariantId')->willReturn('product-001');
        $line->method('getQuantity')->willReturn(1.0);
        $line->method('getId')->willReturn('line-001');

        $purchaseOrder = $this->createMock(PurchaseOrderInterface::class);
        $purchaseOrder->method('getId')->willReturn('po-001');

        $this->procurementManager
            ->expects($this->once())
            ->method('createDirectPO')
            ->with(
                $this->anything(),
                'system', // Should fallback to 'system' when confirmedBy is null
                $this->anything()
            )
            ->willReturn($purchaseOrder);

        // Act
        $this->coordinator->createDropshipPo($salesOrder, [$line], 'vendor-001');

        // Assert
        $this->assertTrue(true); // Verified by mock expectation
    }

    public function test_create_dropship_po_with_multiple_lines(): void
    {
        // Arrange
        $salesOrder = $this->createMock(SalesOrderInterface::class);
        $salesOrder->method('getTenantId')->willReturn('tenant-001');
        $salesOrder->method('getCustomerId')->willReturn('customer-001');
        $salesOrder->method('getOrderNumber')->willReturn('SO-12345');
        $salesOrder->method('getId')->willReturn('so-001');
        $salesOrder->method('getCurrencyCode')->willReturn('USD');
        $salesOrder->method('getShippingAddress')->willReturn([]);
        $salesOrder->method('getConfirmedBy')->willReturn('user-001');

        $line1 = $this->createMock(SalesOrderLineInterface::class);
        $line1->method('getProductVariantId')->willReturn('product-001');
        $line1->method('getQuantity')->willReturn(5.0);
        $line1->method('getId')->willReturn('line-001');

        $line2 = $this->createMock(SalesOrderLineInterface::class);
        $line2->method('getProductVariantId')->willReturn('product-002');
        $line2->method('getQuantity')->willReturn(3.0);
        $line2->method('getId')->willReturn('line-002');

        $purchaseOrder = $this->createMock(PurchaseOrderInterface::class);
        $purchaseOrder->method('getId')->willReturn('po-001');

        $this->procurementManager
            ->expects($this->once())
            ->method('createDirectPO')
            ->with(
                $this->anything(),
                $this->anything(),
                $this->callback(function ($data) {
                    return count($data['items']) === 2
                        && $data['items'][0]['product_id'] === 'product-001'
                        && $data['items'][1]['product_id'] === 'product-002';
                })
            )
            ->willReturn($purchaseOrder);

        // Act
        $poId = $this->coordinator->createDropshipPo(
            $salesOrder,
            [$line1, $line2],
            'vendor-001'
        );

        // Assert
        $this->assertSame('po-001', $poId);
    }
}
