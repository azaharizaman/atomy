<?php

declare(strict_types=1);

namespace Nexus\Inventory\Core\Engine;

use Nexus\Inventory\Contracts\ValuationEngineInterface;
use Nexus\Inventory\Enums\ValuationMethod;

/**
 * FIFO (First-In-First-Out) valuation engine
 * 
 * Uses queue-based cost layers
 * Complexity: O(1) insert, O(n) consume
 */
final class FifoEngine implements ValuationEngineInterface
{
    /**
     * Cost layers stored externally (injected repository)
     * 
     * @var array<string, array<array{quantity: float, unit_cost: float}>>
     */
    private array $costLayers = [];
    
    public function __construct(
        private readonly CostLayerStorageInterface $storage
    ) {
    }
    
    public function getMethod(): ValuationMethod
    {
        return ValuationMethod::FIFO;
    }
    
    public function processReceipt(string $productId, float $quantity, float $unitCost): void
    {
        // Add new cost layer
        $this->storage->addLayer($productId, $quantity, $unitCost);
    }
    
    public function calculateCOGS(string $productId, float $quantity): float
    {
        $remainingQty = $quantity;
        $totalCost = 0.0;
        
        $layers = $this->storage->getLayers($productId);
        
        foreach ($layers as $layer) {
            if ($remainingQty <= 0) {
                break;
            }
            
            $consumeQty = min($remainingQty, $layer['quantity']);
            $totalCost += $consumeQty * $layer['unit_cost'];
            
            // Update layer
            $this->storage->consumeFromLayer($productId, $layer['id'], $consumeQty);
            
            $remainingQty -= $consumeQty;
        }
        
        return $totalCost;
    }
    
    public function getCurrentCost(string $productId): ?float
    {
        $layers = $this->storage->getLayers($productId);
        
        if (empty($layers)) {
            return null;
        }
        
        // Return cost of oldest layer
        return $layers[0]['unit_cost'] ?? null;
    }

    public function capitalizeLandedCost(string $productId, float $additionalCost): void
    {
        $layers = $this->storage->getLayers($productId);
        
        if (empty($layers)) {
            return;
        }
        
        // Target the most recent layer for landed cost capitalization
        $lastLayer = end($layers);
        $this->storage->updateLayerCost($productId, $lastLayer['id'], $additionalCost);
    }
}

/**
 * Internal contract for cost layer storage
 */
interface CostLayerStorageInterface
{
    public function addLayer(string $productId, float $quantity, float $unitCost): void;
    public function getLayers(string $productId): array;
    public function consumeFromLayer(string $productId, string $layerId, float $quantity): void;
    public function updateLayerCost(string $productId, string $layerId, float $additionalCost): void;
}
