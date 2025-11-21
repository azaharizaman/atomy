<?php

declare(strict_types=1);

namespace Nexus\Inventory\Contracts;

/**
 * Lot management interface for FEFO enforcement
 */
interface LotManagerInterface
{
    /**
     * Create new lot with expiry date
     * 
     * @param string $lotNumber Lot number
     * @param string $productId Product identifier
     * @param \DateTimeImmutable $expiryDate Expiry date
     * @param string|null $manufacturingDate Optional manufacturing date
     * @return string Lot ID
     */
    public function createLot(
        string $lotNumber,
        string $productId,
        \DateTimeImmutable $expiryDate,
        ?\DateTimeImmutable $manufacturingDate = null
    ): string;
    
    /**
     * Assign lot to stock receipt
     * 
     * Publishes: LotAssignedEvent
     * 
     * @param string $lotId Lot identifier
     * @param string $productId Product identifier
     * @param string $warehouseId Warehouse identifier
     * @param float $quantity Quantity assigned to lot
     * @return void
     */
    public function assignLotToReceipt(
        string $lotId,
        string $productId,
        string $warehouseId,
        float $quantity
    ): void;
    
    /**
     * Get lots prioritized by FEFO (First-Expiry-First-Out)
     * 
     * @param string $productId Product identifier
     * @param string $warehouseId Warehouse identifier
     * @return array<array{lot_id: string, lot_number: string, quantity: float, expiry_date: string}>
     */
    public function getLotsForIssue(string $productId, string $warehouseId): array;
    
    /**
     * Issue stock from specific lot
     * 
     * @param string $lotId Lot identifier
     * @param float $quantity Quantity to issue
     * @return void
     */
    public function issueFromLot(string $lotId, float $quantity): void;
}
