<?php

declare(strict_types=1);

namespace Nexus\Inventory\Contracts;

use Nexus\Inventory\Enums\ValuationMethod;

/**
 * Inventory valuation engine contract
 * 
 * Implementations: FifoEngine, WeightedAverageEngine, StandardCostEngine
 */
interface ValuationEngineInterface
{
    /**
     * Get the valuation method handled by this engine
     */
    public function getMethod(): ValuationMethod;
    
    /**
     * Process stock receipt and calculate unit cost
     * 
     * @param string $productId Product identifier
     * @param float $quantity Quantity received
     * @param float $unitCost Cost per unit
     * @return void
     */
    public function processReceipt(string $productId, float $quantity, float $unitCost): void;
    
    /**
     * Calculate cost of goods sold for stock issue
     * 
     * @param string $productId Product identifier
     * @param float $quantity Quantity issued
     * @return float Total COGS for the issue
     */
    public function calculateCOGS(string $productId, float $quantity): float;
    
    /**
     * Get current average cost for a product
     * 
     * @param string $productId Product identifier
     * @return float|null Current unit cost, null if no stock
     */
    public function getCurrentCost(string $productId): ?float;
}
