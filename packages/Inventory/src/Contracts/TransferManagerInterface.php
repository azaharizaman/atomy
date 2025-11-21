<?php

declare(strict_types=1);

namespace Nexus\Inventory\Contracts;

use Nexus\Inventory\Enums\TransferStatus;

/**
 * Stock transfer management interface
 */
interface TransferManagerInterface
{
    /**
     * Create stock transfer between warehouses
     * 
     * Publishes: StockTransferredEvent (status: pending)
     * 
     * @param string $productId Product identifier
     * @param string $fromWarehouseId Source warehouse
     * @param string $toWarehouseId Destination warehouse
     * @param float $quantity Quantity to transfer
     * @param string|null $referenceId Optional reference
     * @return string Transfer ID
     */
    public function createTransfer(
        string $productId,
        string $fromWarehouseId,
        string $toWarehouseId,
        float $quantity,
        ?string $referenceId = null
    ): string;
    
    /**
     * Mark transfer as in-transit (deduct from source)
     * 
     * @param string $transferId Transfer identifier
     * @return void
     */
    public function shipTransfer(string $transferId): void;
    
    /**
     * Complete transfer (add to destination)
     * 
     * @param string $transferId Transfer identifier
     * @param float|null $receivedQty Actual received quantity (defaults to transferred qty)
     * @return void
     */
    public function receiveTransfer(string $transferId, ?float $receivedQty = null): void;
    
    /**
     * Cancel pending transfer
     * 
     * @param string $transferId Transfer identifier
     * @return void
     */
    public function cancelTransfer(string $transferId): void;
    
    /**
     * Get transfer status
     * 
     * @param string $transferId Transfer identifier
     * @return TransferStatus
     */
    public function getTransferStatus(string $transferId): TransferStatus;
}
