<?php

declare(strict_types=1);

namespace Nexus\CostAccounting\Contracts;

use Nexus\CostAccounting\Entities\ProductCost;

/**
 * Product Cost Query Interface
 * 
 * Defines read operations for product costs (CQRS pattern).
 */
interface ProductCostQueryInterface
{
    /**
     * Find product cost by product and period
     */
    public function findByProduct(string $productId, string $periodId): ?ProductCost;

    /**
     * Find all product costs for a cost center
     */
    public function findByCostCenter(string $costCenterId, string $periodId): array;

    /**
     * Find all product costs for a period
     */
    public function findByPeriod(string $periodId): array;

    /**
     * Get cost history for a product
     */
    public function getCostHistory(string $productId): array;

    /**
     * Find standard cost for a product
     */
    public function findStandardCost(string $productId, string $periodId): ?ProductCost;

    /**
     * Find actual cost for a product
     */
    public function findActualCost(string $productId, string $periodId): ?ProductCost;

    /**
     * Get all products with costs in a period
     */
    public function getProductsWithCosts(string $periodId): array;

    /**
     * Calculate total costs for a period
     */
    public function calculateTotalCosts(string $periodId): float;
}
