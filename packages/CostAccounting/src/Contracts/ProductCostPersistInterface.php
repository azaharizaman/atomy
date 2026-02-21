<?php

declare(strict_types=1);

namespace Nexus\CostAccounting\Contracts;

use Nexus\CostAccounting\Entities\ProductCost;

/**
 * Product Cost Persist Interface
 * 
 * Defines write operations for product costs (CQRS pattern).
 */
interface ProductCostPersistInterface
{
    /**
     * Save a new product cost
     */
    public function save(ProductCost $productCost): void;

    /**
     * Update an existing product cost
     */
    public function update(ProductCost $productCost): void;

    /**
     * Delete a product cost
     */
    public function delete(string $id): void;

    /**
     * Save multiple product costs in a batch
     */
    public function saveBatch(array $productCosts): void;

    /**
     * Update multiple product costs in a batch
     */
    public function updateBatch(array $productCosts): void;

    /**
     * Delete product costs for a period
     */
    public function deleteByPeriod(string $periodId): void;
}
