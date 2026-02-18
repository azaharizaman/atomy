<?php

declare(strict_types=1);

namespace Nexus\SupplyChainOperations\Listeners;

use Nexus\Inventory\Events\StockIssuedEvent;
use Nexus\Inventory\Events\StockAdjustedEvent;
use Nexus\Inventory\Contracts\StockManagerInterface;
use Nexus\Setting\Contracts\SettingRepositoryInterface;
use Nexus\SupplyChainOperations\Coordinators\ReplenishmentCoordinator;
use Nexus\Tenant\Contracts\TenantContextInterface;
use Nexus\AuditLogger\Services\AuditLogManager;
use Psr\Log\LoggerInterface;

/**
 * Listens for inventory changes and triggers replenishment logic.
 */
final readonly class StockLevelListener
{
    private const REORDER_POINT_SETTING_PREFIX = 'supply_chain.reorder_point.';

    public function __construct(
        private StockManagerInterface $stockManager,
        private SettingRepositoryInterface $settings,
        private ReplenishmentCoordinator $replenishmentCoordinator,
        private TenantContextInterface $tenantContext,
        private AuditLogManager $auditLogger,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Handle stock issue events.
     */
    public function onStockIssued(StockIssuedEvent $event): void
    {
        $this->evaluateReplenishment(
            $event->productId,
            $event->warehouseId
        );
    }

    /**
     * Handle stock adjustment events.
     */
    public function onStockAdjusted(StockAdjustedEvent $event): void
    {
        $this->evaluateReplenishment(
            $event->productId,
            $event->warehouseId
        );
    }

    /**
     * Common logic to evaluate if replenishment is needed.
     */
    private function evaluateReplenishment(string $productId, string $warehouseId): void
    {
        $tenantId = $this->tenantContext->getCurrentTenantId();
        if (!$tenantId) {
            $this->logger->warning('StockLevelListener: No tenant context found.');
            return;
        }

        // 1. Get current stock level
        $currentStock = $this->stockManager->getCurrentStock($productId, $warehouseId);

        // 2. Get reorder point from settings
        $settingKey = self::REORDER_POINT_SETTING_PREFIX . "{$productId}.{$warehouseId}";
        $reorderPoint = $this->settings->get($settingKey);

        if ($reorderPoint === null) {
            // No threshold set for this product/warehouse combination
            return;
        }

        // 3. Trigger replenishment if below threshold
        if ($currentStock <= (float) $reorderPoint) {
            $this->logger->info("Stock level ({$currentStock}) at or below reorder point ({$reorderPoint}) for product {$productId} in warehouse {$warehouseId}.");

            // In this "Enrichment" phase, we might just suggest replenishment or create a draft.
            // For now, we'll trigger the creation of a draft requisition.
            // Ideally, we'd have a 'reorder_quantity' setting too.
            $reorderQtyKey = "supply_chain.reorder_quantity.{$productId}.{$warehouseId}";
            $reorderQty = $this->settings->get($reorderQtyKey, 10.0); // Default to 10 if not set

            $this->replenishmentCoordinator->createAutoRequisition(
                $tenantId,
                $warehouseId,
                'system_auto_replenishment',
                [$productId => (float) $reorderQty]
            );

            $this->auditLogger->log(
                logName: 'supply_chain_replenishment_triggered',
                message: "Replenishment triggered for {$productId} via StockLevelListener",
                context: [
                    'product_id' => $productId,
                    'warehouse_id' => $warehouseId,
                    'current_stock' => $currentStock,
                    'reorder_point' => $reorderPoint
                ]
            );
        }
    }
}
