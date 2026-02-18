<?php

declare(strict_types=1);

namespace Nexus\SupplyChainOperations\Services;

use Nexus\Geo\Contracts\DistanceCalculatorInterface;
use Nexus\Inventory\Contracts\StockLevelRepositoryInterface;
use Psr\Log\LoggerInterface;

final readonly class RegionalOptimizationService
{
    public function __construct(
        private DistanceCalculatorInterface $distanceCalculator,
        private StockLevelRepositoryInterface $stockLevelRepository,
        private LoggerInterface $logger
    ) {
    }

    public function calculateOptimalDistribution(
        string $tenantId,
        array $warehouses,
        array $demandForecast
    ): array {
        $this->logger->info("Calculating optimal distribution for " . count($warehouses) . " warehouses");

        $optimizationResults = [];

        foreach ($demandForecast as $productId => $forecast) {
            $regionalDemand = $forecast['regional_demand'] ?? 0;
            $currentSupply = $this->getCurrentSupplyForProduct($tenantId, $warehouses, $productId);
            
            $allocation = $this->optimizeAllocation(
                $warehouses,
                $regionalDemand,
                $currentSupply
            );

            $optimizationResults[$productId] = $allocation;
        }

        return $optimizationResults;
    }

    public function calculateShippingCostOptimization(
        string $sourceWarehouseId,
        string $destinationWarehouseId,
        float $quantity,
        array $warehouseLocations
    ): float {
        $sourceLocation = $warehouseLocations[$sourceWarehouseId] ?? null;
        $destLocation = $warehouseLocations[$destinationWarehouseId] ?? null;

        if (!$sourceLocation || !$destLocation) {
            return PHP_FLOAT_MAX;
        }

        $distance = $this->distanceCalculator->calculate(
            $sourceLocation['lat'],
            $sourceLocation['lon'],
            $destLocation['lat'],
            $destLocation['lon']
        );

        $costPerUnit = 0.5;
        $distanceCost = $distance * 0.1;
        
        return ($quantity * $costPerUnit) + ($distance * $distanceCost);
    }

    public function findNearestWarehouseWithStock(
        string $tenantId,
        string $productId,
        array $candidateWarehouses,
        float $requiredQuantity,
        array $warehouseLocations
    ): ?array {
        $bestOption = null;
        $lowestCost = PHP_FLOAT_MAX;

        foreach ($candidateWarehouses as $warehouse) {
            $stockLevel = $this->stockLevelRepository->find(
                $tenantId,
                $warehouse['id'],
                $productId
            );

            if (!$stockLevel || $stockLevel->getQuantity() < $requiredQuantity) {
                continue;
            }

            $cost = $this->calculateShippingCostOptimization(
                $warehouse['id'],
                $candidateWarehouses[0]['id'] ?? '',
                $requiredQuantity,
                $warehouseLocations
            );

            if ($cost < $lowestCost) {
                $lowestCost = $cost;
                $bestOption = [
                    'warehouse_id' => $warehouse['id'],
                    'quantity_available' => $stockLevel->getQuantity(),
                    'estimated_cost' => $cost
                ];
            }
        }

        return $bestOption;
    }

    private function getCurrentSupplyForProduct(
        string $tenantId,
        array $warehouses,
        string $productId
    ): float {
        $totalSupply = 0.0;

        foreach ($warehouses as $warehouse) {
            $stockLevel = $this->stockLevelRepository->find(
                $tenantId,
                $warehouse['id'],
                $productId
            );

            if ($stockLevel) {
                $totalSupply += $stockLevel->getQuantity();
            }
        }

        return $totalSupply;
    }

    private function optimizeAllocation(
        array $warehouses,
        float $regionalDemand,
        float $currentSupply
    ): array {
        if ($currentSupply < $regionalDemand) {
            $shortage = $regionalDemand - $currentSupply;
            return [
                'status' => 'shortage',
                'shortage_quantity' => $shortage,
                'recommended_reorder' => $shortage * 1.2,
                'allocation' => []
            ];
        }

        $allocationPerWarehouse = $regionalDemand / count($warehouses);
        $allocation = [];

        foreach ($warehouses as $warehouse) {
            $allocation[$warehouse['id']] = $allocationPerWarehouse;
        }

        return [
            'status' => 'balanced',
            'shortage_quantity' => 0,
            'recommended_reorder' => 0,
            'allocation' => $allocation
        ];
    }
}
