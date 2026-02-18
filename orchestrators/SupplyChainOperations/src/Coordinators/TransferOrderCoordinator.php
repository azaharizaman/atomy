<?php

declare(strict_types=1);

namespace Nexus\SupplyChainOperations\Coordinators;

use Nexus\Inventory\Contracts\TransferManagerInterface;
use Nexus\AuditLogger\Services\AuditLogManager;
use Psr\Log\LoggerInterface;

final readonly class TransferOrderCoordinator
{
    public function __construct(
        private TransferManagerInterface $transferManager,
        private AuditLogManager $auditLogger,
        private LoggerInterface $logger
    ) {
    }

    public function createRegionalTransfer(
        string $tenantId,
        string $productId,
        string $sourceWarehouseId,
        string $destinationWarehouseId,
        float $quantity,
        ?string $reason = null
    ): string {
        $this->logger->info(
            "Creating regional transfer for product {$productId} from {$sourceWarehouseId} to {$destinationWarehouseId}"
        );

        $transferId = $this->transferManager->createTransfer(
            tenantId: $tenantId,
            productId: $productId,
            sourceWarehouseId: $sourceWarehouseId,
            destinationWarehouseId: $destinationWarehouseId,
            quantity: $quantity,
            reason: $reason ?? 'regional_distribution'
        );

        $this->auditLogger->log(
            logName: 'supply_chain_regional_transfer_created',
            message: "Regional transfer {$transferId} created for product {$productId}",
            context: [
                'transfer_id' => $transferId,
                'product_id' => $productId,
                'source_warehouse_id' => $sourceWarehouseId,
                'destination_warehouse_id' => $destinationWarehouseId,
                'quantity' => $quantity,
            ]
        );

        return $transferId;
    }

    public function createBalancingTransfers(
        string $tenantId,
        array $transferRequests
    ): array {
        $createdTransferIds = [];

        foreach ($transferRequests as $request) {
            $transferId = $this->createRegionalTransfer(
                tenantId: $tenantId,
                productId: $request['product_id'],
                sourceWarehouseId: $request['source_warehouse_id'],
                destinationWarehouseId: $request['destination_warehouse_id'],
                quantity: $request['quantity'],
                reason: $request['reason'] ?? null
            );

            $createdTransferIds[] = $transferId;
        }

        $this->logger->info(
            "Created " . count($createdTransferIds) . " balancing transfers for tenant {$tenantId}"
        );

        return $createdTransferIds;
    }
}
