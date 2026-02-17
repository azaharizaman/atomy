<?php

declare(strict_types=1);

namespace Nexus\Inventory\Core\Engine;

use Nexus\Inventory\Contracts\ValuationEngineInterface;
use Nexus\Inventory\Enums\ValuationMethod;

/**
 * Standard Cost valuation engine
 * 
 * Uses fixed cost from configuration + variance tracking
 * Complexity: O(1) for all operations
 */
final class StandardCostEngine implements ValuationEngineInterface
{
    public function __construct(
        private readonly StandardCostStorageInterface $storage
    ) {
    }
    
    public function getMethod(): ValuationMethod
    {
        return ValuationMethod::STANDARD_COST;
    }
    
    public function processReceipt(string $productId, float $quantity, float $unitCost): void
    {
        $standardCost = $this->storage->getStandardCost($productId);
        
        if ($standardCost === null) {
            // Set initial standard cost
            $this->storage->setStandardCost($productId, $unitCost);
            return;
        }
        
        // Calculate purchase price variance
        $variance = ($unitCost - $standardCost) * $quantity;
        
        if (abs($variance) > 0.01) {
            $this->storage->recordVariance($productId, 'purchase_price', $variance);
        }
    }
    
    public function calculateCOGS(string $productId, float $quantity): float
    {
        $standardCost = $this->storage->getStandardCost($productId);
        
        if ($standardCost === null) {
            return 0.0;
        }
        
        return $quantity * $standardCost;
    }
    
    public function getCurrentCost(string $productId): ?float
    {
        return $this->storage->getStandardCost($productId);
    }

    public function capitalizeLandedCost(string $productId, float $additionalCost): void
    {
        // For standard cost, any additional landed costs are recorded as price variances
        $this->storage->recordVariance($productId, 'landed_cost_variance', $additionalCost);
    }
}

/**
 * Internal contract for standard cost storage
 */
interface StandardCostStorageInterface
{
    public function getStandardCost(string $productId): ?float;
    public function setStandardCost(string $productId, float $cost): void;
    public function recordVariance(string $productId, string $type, float $amount): void;
}
