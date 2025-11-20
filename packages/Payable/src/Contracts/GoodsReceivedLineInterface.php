<?php

declare(strict_types=1);

namespace Nexus\Payable\Contracts;

/**
 * Goods Received Note Line entity interface.
 * 
 * This interface defines the contract for goods received note line entities
 * required by the 3-way matching engine in Nexus\Payable.
 * 
 * The concrete implementation should be provided by Nexus\Inventory.
 */
interface GoodsReceivedLineInterface
{
    /**
     * Get the received quantity.
     *
     * @return float Received quantity
     */
    public function getQuantity(): float;

    /**
     * Get the line reference identifier.
     *
     * @return string Line reference (e.g., "GRN-2024-001-L1")
     */
    public function getLineReference(): string;
}
