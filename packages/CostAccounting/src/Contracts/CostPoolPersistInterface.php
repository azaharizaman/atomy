<?php

declare(strict_types=1);

namespace Nexus\CostAccounting\Contracts;

use Nexus\CostAccounting\Entities\CostPool;
use Nexus\CostAccounting\Entities\CostAllocationRule;

/**
 * Cost Pool Persist Interface
 * 
 * CQRS: Write operations for cost pools.
 * Handles all mutations and persistence operations.
 */
interface CostPoolPersistInterface
{
    /**
     * Save a cost pool
     * 
     * @param CostPool $costPool Cost pool entity
     * @return void
     */
    public function save(CostPool $costPool): void;

    /**
     * Delete a cost pool
     * 
     * @param string $id Cost pool identifier
     * @return void
     */
    public function delete(string $id): void;

    /**
     * Update cost pool amount
     * 
     * @param string $id Cost pool identifier
     * @param float $amount New total amount
     * @return void
     */
    public function updateAmount(string $id, float $amount): void;

    /**
     * Add allocation rule
     * 
     * @param string $poolId Cost pool identifier
     * @param CostAllocationRule $rule Allocation rule
     * @return void
     */
    public function addAllocationRule(string $poolId, CostAllocationRule $rule): void;

    /**
     * Remove allocation rule
     * 
     * @param string $poolId Cost pool identifier
     * @param string $ruleId Rule identifier
     * @return void
     */
    public function removeAllocationRule(string $poolId, string $ruleId): void;

    /**
     * Update allocation rules
     * 
     * @param string $poolId Cost pool identifier
     * @param array<CostAllocationRule> $rules Allocation rules
     * @return void
     */
    public function updateAllocationRules(string $poolId, array $rules): void;
}
