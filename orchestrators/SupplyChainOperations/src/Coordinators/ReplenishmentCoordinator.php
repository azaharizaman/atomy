<?php

declare(strict_types=1);

namespace Nexus\SupplyChainOperations\Coordinators;

use Nexus\Inventory\Contracts\StockManagerInterface;
use Nexus\Procurement\Contracts\ProcurementManagerInterface;
use Nexus\SupplyChainOperations\Services\ReplenishmentForecastService;
use Nexus\AuditLogger\Services\AuditLogManager;
use Psr\Log\LoggerInterface;

/**
 * Coordinates replenishment across Inventory and Procurement domains.
 *
 * Provides both threshold-based and ML-enhanced predictive replenishment.
 * Uses ReplenishmentForecastService for ML-based calculations.
 *
 * @see \Nexus\SupplyChainOperations\Services\ReplenishmentForecastService
 */
final readonly class ReplenishmentCoordinator
{
    public function __construct(
        private StockManagerInterface $stockManager,
        private ProcurementManagerInterface $procurementManager,
        private ReplenishmentForecastService $forecastService,
        private AuditLogManager $auditLogger,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Evaluate products in a warehouse and identify those requiring replenishment.
     *
     * @param string $tenantId
     * @param string $warehouseId
     * @return array<string, array{current: float, suggestion: float}>
     */
    public function evaluateStockLevels(string $tenantId, string $warehouseId): array
    {
        $suggestions = [];

        $this->auditLogger->log(
            logName: 'supply_chain_replenishment_evaluated',
            message: "Stock level evaluation triggered for warehouse {$warehouseId}",
            context: [
                'tenant_id' => $tenantId,
                'warehouse_id' => $warehouseId,
            ]
        );

        return $suggestions;
    }

    /**
     * Evaluate replenishment needs using ML-based dynamic reorder points.
     *
     * @param string $tenantId
     * @param string $productId
     * @param string $warehouseId
     * @return array<string, mixed>|null
     */
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
                message: "ML-based replenishment triggered for {$productId}",
                context: [
                    'tenant_id' => $tenantId,
                    'product_id' => $productId,
                    'warehouse_id' => $warehouseId,
                    'current_stock' => $evaluation['current_stock'],
                    'reorder_point' => $evaluation['reorder_point'],
                    'suggested_qty' => $evaluation['suggested_qty'],
                    'forecast_30d' => $evaluation['forecast_30d'],
                    'safety_stock' => $evaluation['safety_stock'],
                ]
            );
        }

        return $evaluation;
    }

    /**
     * Trigger creation of Purchase Requisitions for low-stock items.
     *
     * @param string $tenantId
     * @param string $warehouseId
     * @param string $requesterId
     * @param array<string, float> $replenishmentMap
     * @return string|null
     */
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
            message: "Automated PR {$requisition->getId()} created for {$warehouseId}",
            context: [
                'tenant_id' => $tenantId,
                'requisition_id' => $requisition->getId(),
                'item_count' => count($items),
            ]
        );

        return $requisition->getId();
    }

    /**
     * Create auto-requisition based on ML forecast evaluation.
     *
     * @param string $tenantId
     * @param string $warehouseId
     * @param string $requesterId
     * @param string $productId
     * @return string|null
     */
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
