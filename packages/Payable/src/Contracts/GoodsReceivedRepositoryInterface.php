<?php

declare(strict_types=1);

namespace Nexus\Payable\Contracts;

/**
 * Repository interface for goods received note operations.
 * 
 * This interface defines the contract that Nexus\Payable requires
 * from the Nexus\Inventory package for 3-way matching operations.
 * 
 * The concrete implementation should be provided by Nexus\Inventory
 * and bound in the application layer (Atomy).
 */
interface GoodsReceivedRepositoryInterface
{
    /**
     * Find goods received note line by reference.
     *
     * @param string $lineReference GRN line reference (e.g., "GRN-2024-001-L1")
     * @return GoodsReceivedLineInterface|null GRN line entity
     */
    public function findLineByReference(string $lineReference): ?GoodsReceivedLineInterface;
}
