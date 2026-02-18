<?php

declare(strict_types=1);

namespace Nexus\SupplyChainOperations\Coordinators;

use Nexus\SupplyChainOperations\Contracts\SupplyChainStockManagerInterface;
use Nexus\SupplyChainOperations\Contracts\ProcurementManagerInterface;
use Nexus\SupplyChainOperations\Contracts\ReplenishmentForecastServiceInterface;
use Nexus\SupplyChainOperations\Contracts\AuditLoggerInterface;
use Psr\Log\LoggerInterface;

final readonly class ReplenishmentCoordinator
{
    public function __construct(
        private SupplyChainStockManagerInterface $stockManager,
        private ProcurementManagerInterface $procurementManager,
        private ReplenishmentForecastServiceInterface $forecastService,
        private AuditLoggerInterface $auditLogger,
        private LoggerInterface $logger
    ) {
    }

    public function evaluateStockLevels(string $tenantId, string $warehouseId): array
    {
        $suggestions = [];

        $this->auditLogger->log(
            logName: 'supply_chain_replenishment_evaluated',
            description: "Stock level evaluation triggered for warehouse {$warehouseId}"
        );

        return $suggestions;
    }

    public function evaluateProductWithForecast(
        string $tenantId,
        string $productId,
        string $warehouseId
    ): ?array {
        $currentStock = $this->stockManager->getCurrentStock($productId, $warehouseId);

        $evaluation = $this->forecastService->evaluateWithForecast(
            $productId,
            $warehouseId,
            $currentStock
        );

        if ($evaluation === null) {
            return null;
        }

        if ($evaluation['requires_reorder']) {
            $this->auditLogger->log(
                logName: 'supply_chain_ml_replenishment_triggered',
                description: "ML-based replenishment triggered for {$productId}"
            );
        }

        return $evaluation;
    }

    public function createAutoRequisition(
        string $tenantId,
        string $warehouseId,
        string $requesterId,
        array $replenishmentMap
    ): ?string {
        if (empty($replenishmentMap)) {
            return null;
        }

        $items = [];
        foreach ($replenishmentMap as $productId => $quantity) {
            $items[] = [
                'product_id' => $productId,
                'quantity' => $quantity,
                'warehouse_id' => $warehouseId,
                'priority' => 'NORMAL',
                'metadata' => [
                    'source' => 'ReplenishmentCoordinator',
                    'reason' => 'AUTO_THRESHOLD_REPLENISHMENT'
                ]
            ];
        }

        $requisition = $this->procurementManager->createRequisition($tenantId, $requesterId, [
            'type' => 'STOCK_REPLENISHMENT',
            'description' => "Automated replenishment for warehouse {$warehouseId}",
            'items' => $items,
        ]);

        $this->auditLogger->log(
            logName: 'supply_chain_auto_requisition_created',
            description: "Automated PR {$requisition->getId()} created for {$warehouseId}"
        );

        return $requisition->getId();
    }

    public function createForecastBasedRequisition(
        string $tenantId,
        string $warehouseId,
        string $requesterId,
        string $productId
    ): ?string {
        $evaluation = $this->evaluateProductWithForecast($tenantId, $productId, $warehouseId);

        if ($evaluation === null || !$evaluation['requires_reorder']) {
            return null;
        }

        return $this->createAutoRequisition(
            $tenantId,
            $warehouseId,
            $requesterId,
            [$productId => $evaluation['suggested_qty']]
        );
    }
}
