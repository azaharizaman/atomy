<?php

declare(strict_types=1);

namespace Nexus\Inventory\Services;

use Nexus\Inventory\Contracts\StockManagerInterface;
use Nexus\Inventory\Contracts\ValuationEngineInterface;
use Nexus\Inventory\Contracts\StockLevelRepositoryInterface;
use Nexus\Inventory\Contracts\StockMovementRepositoryInterface;
use Nexus\Inventory\Enums\IssueReason;
use Nexus\Inventory\Enums\MovementType;
use Nexus\Inventory\Events\StockReceivedEvent;
use Nexus\Inventory\Events\StockIssuedEvent;
use Nexus\Inventory\Events\StockAdjustedEvent;
use Nexus\Inventory\Exceptions\InsufficientStockException;
use Nexus\Inventory\Exceptions\NegativeStockNotAllowedException;
use Psr\Log\LoggerInterface;

/**
 * Main stock management service
 * 
 * Orchestrates stock operations and publishes domain events
 */
final readonly class StockManager implements StockManagerInterface
{
    public function __construct(
        private StockLevelRepositoryInterface $stockLevelRepository,
        private StockMovementRepositoryInterface $movementRepository,
        private ValuationEngineInterface $valuationEngine,
        private EventPublisherInterface $eventPublisher,
        private ConfigurationInterface $config,
        private LoggerInterface $logger
    ) {
    }
    
    public function receiveStock(
        string $productId,
        string $warehouseId,
        float $quantity,
        float $unitCost,
        ?string $grnId = null,
        ?string $lotId = null
    ): void {
        $this->logger->info('Stock receipt initiated', [
            'product_id' => $productId,
            'warehouse_id' => $warehouseId,
            'quantity' => $quantity,
            'unit_cost' => $unitCost,
        ]);
        
        // Process valuation
        $this->valuationEngine->processReceipt($productId, $quantity, $unitCost);
        
        // Update stock level
        $currentLevel = $this->stockLevelRepository->getCurrentLevel($productId, $warehouseId);
        $newLevel = $currentLevel + $quantity;
        $this->stockLevelRepository->updateLevel($productId, $warehouseId, $newLevel);
        
        // Record movement
        $this->movementRepository->recordMovement(
            $productId,
            $warehouseId,
            MovementType::RECEIPT,
            $quantity,
            $unitCost,
            $grnId
        );
        
        // Publish event for GL integration
        $event = new StockReceivedEvent(
            productId: $productId,
            warehouseId: $warehouseId,
            quantity: $quantity,
            unitCost: $unitCost,
            totalValue: $quantity * $unitCost,
            receivedDate: new \DateTimeImmutable(),
            grnId: $grnId,
            lotId: $lotId
        );
        
        $this->eventPublisher->publish($event);
        
        $this->logger->info('Stock receipt completed', [
            'product_id' => $productId,
            'new_level' => $newLevel,
        ]);
    }
    
    public function issueStock(
        string $productId,
        string $warehouseId,
        float $quantity,
        IssueReason $reason,
        ?string $referenceId = null
    ): float {
        $this->logger->info('Stock issue initiated', [
            'product_id' => $productId,
            'warehouse_id' => $warehouseId,
            'quantity' => $quantity,
            'reason' => $reason->value,
        ]);
        
        // Check availability
        $currentLevel = $this->stockLevelRepository->getCurrentLevel($productId, $warehouseId);
        
        if ($currentLevel < $quantity) {
            $allowNegative = $this->config->allowNegativeStock($productId, $warehouseId);
            
            if (!$allowNegative) {
                throw NegativeStockNotAllowedException::forProduct($productId, $warehouseId);
            }
            
            $this->logger->warning('Negative stock allowed', [
                'product_id' => $productId,
                'current' => $currentLevel,
                'requested' => $quantity,
            ]);
        }
        
        // Calculate COGS
        $cogs = $this->valuationEngine->calculateCOGS($productId, $quantity);
        
        // Update stock level
        $newLevel = $currentLevel - $quantity;
        $this->stockLevelRepository->updateLevel($productId, $warehouseId, $newLevel);
        
        // Record movement
        $unitCost = $quantity > 0 ? $cogs / $quantity : 0.0;
        $this->movementRepository->recordMovement(
            $productId,
            $warehouseId,
            MovementType::ISSUE,
            -$quantity,
            $unitCost,
            $referenceId
        );
        
        // Publish event for GL integration
        $event = new StockIssuedEvent(
            productId: $productId,
            warehouseId: $warehouseId,
            quantity: $quantity,
            costOfGoodsSold: $cogs,
            issuedDate: new \DateTimeImmutable(),
            issueReason: $reason,
            referenceId: $referenceId
        );
        
        $this->eventPublisher->publish($event);
        
        $this->logger->info('Stock issue completed', [
            'product_id' => $productId,
            'new_level' => $newLevel,
            'cogs' => $cogs,
        ]);
        
        return $cogs;
    }
    
    public function adjustStock(
        string $productId,
        string $warehouseId,
        float $adjustmentQty,
        string $reason
    ): void {
        $this->logger->info('Stock adjustment initiated', [
            'product_id' => $productId,
            'warehouse_id' => $warehouseId,
            'adjustment' => $adjustmentQty,
            'reason' => $reason,
        ]);
        
        // Update stock level
        $currentLevel = $this->stockLevelRepository->getCurrentLevel($productId, $warehouseId);
        $newLevel = $currentLevel + $adjustmentQty;
        $this->stockLevelRepository->updateLevel($productId, $warehouseId, $newLevel);
        
        // Record movement
        $currentCost = $this->valuationEngine->getCurrentCost($productId) ?? 0.0;
        $this->movementRepository->recordMovement(
            $productId,
            $warehouseId,
            MovementType::ADJUSTMENT,
            $adjustmentQty,
            $currentCost,
            $reason
        );
        
        // Publish event
        $event = new StockAdjustedEvent(
            productId: $productId,
            warehouseId: $warehouseId,
            adjustmentQuantity: $adjustmentQty,
            reason: $reason,
            adjustedDate: new \DateTimeImmutable()
        );
        
        $this->eventPublisher->publish($event);
        
        $this->logger->info('Stock adjustment completed', [
            'product_id' => $productId,
            'old_level' => $currentLevel,
            'new_level' => $newLevel,
        ]);
    }
    
    public function getCurrentStock(string $productId, string $warehouseId): float
    {
        return $this->stockLevelRepository->getCurrentLevel($productId, $warehouseId);
    }
    
    public function getAvailableStock(string $productId, string $warehouseId): float
    {
        $onHand = $this->stockLevelRepository->getCurrentLevel($productId, $warehouseId);
        $reserved = $this->stockLevelRepository->getReservedQuantity($productId, $warehouseId);
        
        return max(0.0, $onHand - $reserved);
    }
}

/**
 * Event publisher contract (implemented in Atomy layer)
 */
interface EventPublisherInterface
{
    public function publish(object $event): void;
}

/**
 * Configuration contract (implemented in Atomy layer)
 */
interface ConfigurationInterface
{
    public function allowNegativeStock(string $productId, string $warehouseId): bool;
}
