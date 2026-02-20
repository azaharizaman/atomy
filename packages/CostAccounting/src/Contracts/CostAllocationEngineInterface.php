<?php

declare(strict_types=1);

namespace Nexus\CostAccounting\Contracts;

use Nexus\CostAccounting\Entities\CostPool;
use Nexus\CostAccounting\Entities\CostAllocationRule;

/**
 * Cost Allocation Engine Interface
 * 
 * Executes cost allocation from cost pools to receiving
 * cost centers using various allocation methods.
 */
interface CostAllocationEngineInterface
{
    /**
     * Allocate pool costs
     * 
     * @param CostPool $pool Cost pool to allocate from
     * @param string $periodId Fiscal period identifier
     * @return array<string, mixed>
     */
    public function allocate(CostPool $pool, string $periodId): array;

    /**
     * Validate allocation rules
     * 
     * @param CostPool $pool Cost pool to validate
     * @return array<string, mixed>
     */
    public function validateAllocationRules(CostPool $pool): array;

    /**
     * Detect circular dependencies in allocation rules
     * 
     * @param CostAllocationRule $rule Allocation rule to check
     * @return bool
     */
    public function detectCircularDependencies(CostAllocationRule $rule): bool;

    /**
     * Calculate activity rates for ABC
     * 
     * @param string $costCenterId Cost center identifier
     * @param string $periodId Fiscal period identifier
     * @return array<string, mixed>
     */
    public function calculateActivityRates(
        string $costCenterId,
        string $periodId
    ): array;

    /**
     * Execute step-down allocation
     * 
     * @param CostPool $pool Cost pool to allocate
     * @param string $periodId Fiscal period identifier
     * @param array<int, string> $order Allocation order
     * @return array<string, mixed>
     */
    public function allocateStepDown(
        CostPool $pool,
        string $periodId,
        array $order
    ): array;

    /**
     * Execute reciprocal allocation
     * 
     * @param array<CostPool> $pools Cost pools to allocate
     * @param string $periodId Fiscal period identifier
     * @return array<string, mixed>
     */
    public function allocateReciprocal(
        array $pools,
        string $periodId
    ): array;
}
