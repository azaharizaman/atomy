<?php

declare(strict_types=1);

namespace Nexus\CostAccounting\Contracts;

use Nexus\CostAccounting\Entities\CostAllocationRule;
use Nexus\CostAccounting\Enums\AllocationMethod;

/**
 * Cost Allocation Rule Interface
 * 
 * Defines operations for cost allocation rule management.
 */
interface CostAllocationRuleInterface
{
    /**
     * Find rule by ID
     */
    public function findById(string $id): ?CostAllocationRule;

    /**
     * Find all rules for a cost pool
     */
    public function findByPool(string $poolId): array;

    /**
     * Find all rules for a receiving cost center
     */
    public function findByReceivingCostCenter(string $costCenterId): array;

    /**
     * Find active rules for a pool
     */
    public function findActiveRules(string $poolId): array;

    /**
     * Validate that ratios sum to 1.0
     */
    public function validateRatios(array $rules): bool;

    /**
     * Check for circular dependencies
     */
    public function hasCircularDependency(string $poolId, string $receivingCostCenterId): bool;

    /**
     * Calculate allocation based on rules
     */
    public function calculateAllocation(float $totalAmount, array $rules): array;

    /**
     * Save a new rule
     */
    public function save(CostAllocationRule $rule): void;

    /**
     * Update an existing rule
     */
    public function update(CostAllocationRule $rule): void;

    /**
     * Delete a rule
     */
    public function delete(string $id): void;
}
