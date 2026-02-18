<?php

declare(strict_types=1);

namespace Nexus\SupplyChainOperations\Contracts;

interface LandedCostCoordinatorInterface
{
    /**
     * Capitalize a cost amount onto a specific Goods Receipt Note (GRN).
     *
     * @param string $grnId The GRN reference
     * @param float $amount The total landed cost to distribute (freight, duty, etc.)
     * @param string $allocationBasis 'value' or 'quantity' (Default: 'value')
     */
    public function distributeLandedCost(string $grnId, float $amount, string $allocationBasis = 'value'): void;
}
