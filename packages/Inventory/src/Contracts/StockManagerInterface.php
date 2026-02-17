<?php

declare(strict_types=1);

namespace Nexus\Inventory\Contracts;

use Nexus\Inventory\Enums\IssueReason;

/**
 * Main stock management interface
 */
interface StockManagerInterface
{
    /**
     * Receive stock into warehouse
     * 
     * Publishes: StockReceivedEvent
     * 
     * @param string $productId Product identifier
     * @param string $warehouseId Warehouse identifier
     * @param float $quantity Quantity received
     * @param float $unitCost Cost per unit
     * @param string|null $grnId Optional GRN reference
     * @param string|null $lotId Optional lot assignment
     * @return void
     */
    public function receiveStock(
        string $productId,
        string $warehouseId,
        float $quantity,
        float $unitCost,
        ?string $grnId = null,
        ?string $lotId = null
    ): void;
    
    /**
     * Issue stock from warehouse
     * 
     * Publishes: StockIssuedEvent
     * 
     * @param string $productId Product identifier
     * @param string $warehouseId Warehouse identifier
     * @param float $quantity Quantity to issue
     * @param IssueReason $reason Reason for issue
     * @param string|null $referenceId Optional reference (SO, WO, etc.)
     * @return float Cost of goods sold
     */
    public function issueStock(
        string $productId,
        string $warehouseId,
        float $quantity,
        IssueReason $reason,
        ?string $referenceId = null
    ): float;
    
    /**
     * Adjust stock quantity (cycle count, damage, etc.)
     * 
     * Publishes: StockAdjustedEvent
     * 
     * @param string $productId Product identifier
     * @param string $warehouseId Warehouse identifier
     * @param float $adjustmentQty Positive or negative adjustment
     * @param string $reason Adjustment reason
     * @return void
     */
    public function adjustStock(
        string $productId,
        string $warehouseId,
        float $adjustmentQty,
        string $reason
    ): void;
    
    /**
     * Get current stock level
     * 
     * @param string $productId Product identifier
     * @param string $warehouseId Warehouse identifier
     * @return float Current quantity on hand
     */
    public function getCurrentStock(string $productId, string $warehouseId): float;
    
    /**
     * Get available stock (on-hand minus reservations and quarantine)
     * 
     * @param string $productId Product identifier
     * @param string $warehouseId Warehouse identifier
     * @return float Available quantity
     */
    public function getAvailableStock(string $productId, string $warehouseId): float;

    /**
     * Move stock to quarantine (e.g., for quality inspection)
     * 
     * @param string $productId Product identifier
     * @param string $warehouseId Warehouse identifier
     * @param float $quantity Quantity to quarantine
     * @return void
     */
    public function quarantineStock(string $productId, string $warehouseId, float $quantity): void;

    /**
     * Release stock from quarantine to available stock
     * 
     * @param string $productId Product identifier
     * @param string $warehouseId Warehouse identifier
     * @param float $quantity Quantity to release
     * @return void
     */
    public function releaseFromQuarantine(string $productId, string $warehouseId, float $quantity): void;

    /**
     * Capitalize landed costs into inventory valuation
     * 
     * @param string $productId Product identifier
     * @param float $additionalCost Total cost to add to inventory value
     * @return void
     */
    public function capitalizeLandedCost(string $productId, float $additionalCost): void;
}
