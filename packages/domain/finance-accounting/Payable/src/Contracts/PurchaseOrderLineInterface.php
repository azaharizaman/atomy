<?php

declare(strict_types=1);

namespace Nexus\Payable\Contracts;

/**
 * Purchase Order Line entity interface.
 * 
 * This interface defines the contract for purchase order line entities
 * required by the 3-way matching engine in Nexus\Payable.
 * 
 * The concrete implementation should be provided by Nexus\Procurement.
 */
interface PurchaseOrderLineInterface
{
    /**
     * Get the ordered quantity.
     *
     * @return float Ordered quantity
     */
    public function getQuantity(): float;

    /**
     * Get the unit price from the purchase order.
     *
     * @return float Unit price
     */
    public function getUnitPrice(): float;

    /**
     * Get the line reference identifier.
     *
     * @return string Line reference (e.g., "PO-2024-001-L1")
     */
    public function getLineReference(): string;
}
