<?php

declare(strict_types=1);

namespace Nexus\SupplyChainOperations\Workflows\WarehouseBalancing;

use Nexus\Geo\Contracts\GeoRepositoryInterface;
use Nexus\Inventory\Contracts\StockLevelRepositoryInterface;
use Nexus\Inventory\Contracts\TransferManagerInterface;
use Nexus\Warehouse\Contracts\WarehouseRepositoryInterface;
use Nexus\AuditLogger\Services\AuditLogManager;
use Psr\Log\LoggerInterface;

final readonly class WarehouseBalancingWorkflow
{
    public function __construct(
        private GeoRepositoryInterface $geoRepository,
        private StockLevelRepositoryInterface $stockLevelRepository,
        private WarehouseRepositoryInterface $warehouseRepository,
        private TransferManagerInterface $transferManager,
        private AuditLogManager $auditLogger,
        private LoggerInterface $logger
    ) {
    }

    public function analyzeAndBalance(string $tenantId, ?string $regionId = null): BalancingResult
    {
        $this->logger->info("Starting warehouse balancing analysis for tenant {$tenantId}");

        $warehouses = $this->warehouseRepository->findByTenant($tenantId);
        
        if ($regionId !== null) {
            $warehouses = array_filter($warehouses, fn($w) => $w->getRegionId() === $regionId);
        }

        $stockLevels = [];
        foreach ($warehouses as $warehouse) {
            $levels = $this->stockLevelRepository->findByWarehouse($tenantId, $warehouse->getId());
            $stockLevels[$warehouse->getId()] = [
                'warehouse' => $warehouse,
                'levels' => $levels,
                'location' => $this->geoRepository->getLocation($warehouse->getLocationId())
            ];
        }

        $imbalances = $this->identifyImbalances($stockLevels);
        $transferRecommendations = $this->generateTransferRecommendations($imbalances);

        $createdTransfers = [];
        foreach ($transferRecommendations as $recommendation) {
            $transferId = $this->createTransferOrder($tenantId, $recommendation);
            $createdTransfers[] = $transferId;
        }

        $this->auditLogger->log(
            logName: 'warehouse_balancing_completed',
            message: "Warehouse balancing completed: " . count($createdTransfers) . " transfers created",
            context: [
                'tenant_id' => $tenantId,
                'region_id' => $regionId,
                'warehouses_analyzed' => count($warehouses),
                'imbalances_found' => count($imbalances),
                'transfers_created' => $createdTransfers,
            ]
        );

        return new BalancingResult(
            tenantId: $tenantId,
            regionId: $regionId,
            warehousesAnalyzed: count($warehouses),
            imbalancesFound: count($imbalances),
            transfersCreated: $createdTransfers
        );
    }

    private function identifyImbalances(array $stockLevels): array
    {
        $imbalances = [];
        $productDemand = [];

        foreach ($stockLevels as $warehouseId => $data) {
            foreach ($data['levels'] as $level) {
                $productId = $level->getProductId();
                $quantity = $level->getQuantity();
                $reorderPoint = $level->getReorderPoint() ?? 0;

                if (!isset($productDemand[$productId])) {
                    $productDemand[$productId] = [
                        'total_demand' => 0,
                        'warehouses' => []
                    ];
                }

                if ($quantity < $reorderPoint) {
                    $productDemand[$productId]['warehouses'][$warehouseId] = [
                        'shortage' => $reorderPoint - $quantity,
                        'type' => 'deficit'
                    ];
                } elseif ($quantity > ($reorderPoint * 2)) {
                    $productDemand[$productId]['warehouses'][$warehouseId] = [
                        'surplus' => $quantity - ($reorderPoint * 2),
                        'type' => 'surplus'
                    ];
                }
            }
        }

        return $productDemand;
    }

    private function generateTransferRecommendations(array $imbalances): array
    {
        $recommendations = [];

        foreach ($imbalances as $productId => $data) {
            $surplusWarehouses = [];
            $deficitWarehouses = [];

            foreach ($data['warehouses'] as $warehouseId => $info) {
                if (($info['type'] ?? '') === 'surplus') {
                    $surplusWarehouses[$warehouseId] = $info['surplus'];
                } elseif (($info['type'] ?? '') === 'deficit') {
                    $deficitWarehouses[$warehouseId] = $info['shortage'];
                }
            }

            foreach ($deficitWarehouses as $deficitWh => $deficitQty) {
                foreach ($surplusWarehouses as $surplusWh => $surplusQty) {
                    if ($surplusWh === $deficitWh) {
                        continue;
                    }

                    $transferQty = min($deficitQty, $surplusQty);
                    if ($transferQty > 0) {
                        $recommendations[] = [
                            'product_id' => $productId,
                            'source_warehouse_id' => $surplusWh,
                            'destination_warehouse_id' => $deficitWh,
                            'quantity' => $transferQty
                        ];
                    }
                }
            }
        }

        return $recommendations;
    }

    private function createTransferOrder(string $tenantId, array $recommendation): string
    {
        return $this->transferManager->createTransfer(
            tenantId: $tenantId,
            productId: $recommendation['product_id'],
            sourceWarehouseId: $recommendation['source_warehouse_id'],
            destinationWarehouseId: $recommendation['destination_warehouse_id'],
            quantity: $recommendation['quantity'],
            reason: 'warehouse_balancing'
        );
    }
}
