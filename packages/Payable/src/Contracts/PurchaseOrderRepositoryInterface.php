<?php

declare(strict_types=1);

namespace Nexus\Payable\Contracts;

/**
 * Repository interface for purchase order operations.
 * 
 * This interface defines the contract that Nexus\Payable requires
 * from the Nexus\Procurement package for 3-way matching operations.
 * 
 * The concrete implementation should be provided by Nexus\Procurement
 * and bound in the application layer (Atomy).
 */
interface PurchaseOrderRepositoryInterface
{
    /**
     * Find purchase order line by reference.
     *
     * @param string $lineReference PO line reference (e.g., "PO-2024-001-L1")
     * @return object|null PO line object with getQuantity() and getUnitPrice() methods
     */
    public function findLineByReference(string $lineReference): ?object;
}
