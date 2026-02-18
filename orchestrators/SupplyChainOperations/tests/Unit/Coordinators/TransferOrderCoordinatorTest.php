<?php

declare(strict_types=1);

namespace Nexus\SupplyChainOperations\Tests\Unit\Coordinators;

use Nexus\Inventory\Contracts\TransferManagerInterface;
use Nexus\SupplyChainOperations\Coordinators\TransferOrderCoordinator;
use Nexus\AuditLogger\Services\AuditLogManager;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class TransferOrderCoordinatorTest extends TestCase
{
    private TransferManagerInterface $transferManager;
    private AuditLogManager $auditLogger;
    private LoggerInterface $logger;
    private TransferOrderCoordinator $coordinator;

    protected function setUp(): void
    {
        $this->transferManager = $this->createMock(TransferManagerInterface::class);
        $this->auditLogger = $this->createMock(AuditLogManager::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->coordinator = new TransferOrderCoordinator(
            $this->transferManager,
            $this->auditLogger,
            $this->logger
        );
    }

    public function test_create_regional_transfer(): void
    {
        $tenantId = 'tenant-001';
        $productId = 'product-001';
        $sourceWh = 'WH-001';
        $destWh = 'WH-002';
        $quantity = 50.0;

        $this->transferManager
            ->expects($this->once())
            ->method('createTransfer')
            ->with(
                $tenantId,
                $productId,
                $sourceWh,
                $destWh,
                $quantity,
                'regional_distribution'
            )
            ->willReturn('TO-001');

        $this->auditLogger
            ->expects($this->once())
            ->method('log')
            ->with(
                'supply_chain_regional_transfer_created',
                $this->stringContains('Regional transfer TO-001'),
                $this->anything()
            );

        $result = $this->coordinator->createRegionalTransfer(
            $tenantId,
            $productId,
            $sourceWh,
            $destWh,
            $quantity
        );

        $this->assertSame('TO-001', $result);
    }

    public function test_create_regional_transfer_with_custom_reason(): void
    {
        $tenantId = 'tenant-001';
        $productId = 'product-001';
        $sourceWh = 'WH-001';
        $destWh = 'WH-002';
        $quantity = 25.0;
        $reason = 'custom_reason';

        $this->transferManager
            ->expects($this->once())
            ->method('createTransfer')
            ->with(
                $this->anything(),
                $this->anything(),
                $this->anything(),
                $this->anything(),
                $this->anything(),
                $reason
            )
            ->willReturn('TO-002');

        $result = $this->coordinator->createRegionalTransfer(
            $tenantId,
            $productId,
            $sourceWh,
            $destWh,
            $quantity,
            $reason
        );

        $this->assertSame('TO-002', $result);
    }

    public function test_create_balancing_transfers_creates_multiple(): void
    {
        $tenantId = 'tenant-001';
        $requests = [
            [
                'product_id' => 'product-001',
                'source_warehouse_id' => 'WH-001',
                'destination_warehouse_id' => 'WH-002',
                'quantity' => 30.0,
                'reason' => null
            ],
            [
                'product_id' => 'product-002',
                'source_warehouse_id' => 'WH-003',
                'destination_warehouse_id' => 'WH-004',
                'quantity' => 20.0,
                'reason' => null
            ]
        ];

        $this->transferManager
            ->expects($this->exactly(2))
            ->method('createTransfer')
            ->willReturnOnConsecutiveCalls('TO-001', 'TO-002');

        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with($this->stringContains('Created 2 balancing transfers'));

        $results = $this->coordinator->createBalancingTransfers($tenantId, $requests);

        $this->assertCount(2, $results);
        $this->assertSame('TO-001', $results[0]);
        $this->assertSame('TO-002', $results[1]);
    }
}
