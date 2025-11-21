<?php

declare(strict_types=1);

namespace Nexus\Inventory\Core\Engine;

use Nexus\Inventory\Contracts\ValuationEngineInterface;
use Nexus\Inventory\Enums\ValuationMethod;

/**
 * Weighted Average valuation engine
 * 
 * Uses running average formula: (Total Value + New Value) / (Total Qty + New Qty)
 * Complexity: O(1) for both insert and consume
 */
final class WeightedAverageEngine implements ValuationEngineInterface
{
    public function __construct(
        private readonly WeightedAverageStorageInterface $storage
    ) {
    }
    
    public function getMethod(): ValuationMethod
    {
        return ValuationMethod::WEIGHTED_AVERAGE;
    }
    
    public function processReceipt(string $productId, float $quantity, float $unitCost): void
    {
        $current = $this->storage->getCurrent($productId);
        
        if ($current === null) {
            // First receipt
            $this->storage->update($productId, $quantity, $unitCost);
            return;
        }
        
        // Calculate new weighted average
        $totalValue = ($current['quantity'] * $current['average_cost']) + ($quantity * $unitCost);
        $totalQuantity = $current['quantity'] + $quantity;
        $newAverage = $totalQuantity > 0 ? $totalValue / $totalQuantity : 0.0;
        
        $this->storage->update($productId, $totalQuantity, $newAverage);
    }
    
    public function calculateCOGS(string $productId, float $quantity): float
    {
        $current = $this->storage->getCurrent($productId);
        
        if ($current === null) {
            return 0.0;
        }
        
        $cogs = $quantity * $current['average_cost'];
        
        // Update quantity (average cost remains same)
        $newQuantity = $current['quantity'] - $quantity;
        $this->storage->update($productId, $newQuantity, $current['average_cost']);
        
        return $cogs;
    }
    
    public function getCurrentCost(string $productId): ?float
    {
        $current = $this->storage->getCurrent($productId);
        return $current['average_cost'] ?? null;
    }
}

/**
 * Internal contract for weighted average storage
 */
interface WeightedAverageStorageInterface
{
    public function getCurrent(string $productId): ?array;
    public function update(string $productId, float $quantity, float $averageCost): void;
}
