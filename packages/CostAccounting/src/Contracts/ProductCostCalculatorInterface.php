<?php

declare(strict_types=1);

namespace Nexus\CostAccounting\Contracts;

use Nexus\CostAccounting\Entities\ProductCost;
use Nexus\CostAccounting\Enums\CostType;
use Nexus\CostAccounting\ValueObjects\ProductCostSnapshot;

/**
 * Product Cost Calculator Interface
 * 
 * Handles product cost calculation including material, labor,
 * and overhead costs with multi-level rollup support.
 */
interface ProductCostCalculatorInterface
{
    /**
     * Calculate product cost
     * 
     * @param string $productId Product identifier
     * @param string $periodId Fiscal period identifier
     * @param CostType $costType Cost type (actual/standard)
     * @return ProductCost
     */
    public function calculate(
        string $productId,
        string $periodId,
        CostType $costType = CostType::Standard
    ): ProductCost;

    /**
     * Calculate standard cost
     * 
     * @param string $productId Product identifier
     * @param string $periodId Fiscal period identifier
     * @return ProductCost
     */
    public function calculateStandardCost(
        string $productId,
        string $periodId
    ): ProductCost;

    /**
     * Calculate actual cost
     * 
     * @param string $productId Product identifier
     * @param string $periodId Fiscal period identifier
     * @return ProductCost
     */
    public function calculateActualCost(
        string $productId,
        string $periodId
    ): ProductCost;

    /**
     * Perform multi-level cost rollup
     * 
     * @param string $productId Product identifier
     * @param string $periodId Fiscal period identifier
     * @return ProductCostSnapshot
     */
    public function rollup(
        string $productId,
        string $periodId
    ): ProductCostSnapshot;

    /**
     * Calculate unit cost
     * 
     * @param string $productId Product identifier
     * @param string $periodId Fiscal period identifier
     * @param float $quantity Quantity produced
     * @return float
     */
    public function calculateUnitCost(
        string $productId,
        string $periodId,
        float $quantity
    ): float;
}
