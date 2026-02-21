<?php

declare(strict_types=1);

namespace Nexus\CostAccounting\Services;

use Nexus\CostAccounting\Contracts\CostAllocationEngineInterface;
use Nexus\CostAccounting\Contracts\CostPoolPersistInterface;
use Nexus\CostAccounting\Contracts\CostPoolQueryInterface;
use Nexus\CostAccounting\Entities\CostAllocationRule;
use Nexus\CostAccounting\Entities\CostPool;
use Nexus\CostAccounting\Enums\AllocationMethod;
use Nexus\CostAccounting\Events\CostAllocatedEvent;
use Nexus\CostAccounting\Exceptions\AllocationCycleDetectedException;
use Nexus\CostAccounting\Exceptions\InsufficientCostPoolException;
use Nexus\CostAccounting\Exceptions\InvalidAllocationRuleException;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

/**
 * Cost Allocation Engine Service
 * 
 * Executes cost allocation from cost pools to receiving
 * cost centers using various allocation methods.
 */
final readonly class CostAllocationEngine implements CostAllocationEngineInterface
{
    public function __construct(
        private CostPoolQueryInterface $costPoolQuery,
        private CostPoolPersistInterface $costPoolPersist,
        private EventDispatcherInterface $eventDispatcher,
        private LoggerInterface $logger
    ) {}

    /**
     * {@inheritdoc}
     */
    public function allocate(CostPool $pool, string $periodId): array
    {
        $this->logger->info('Allocating pool costs', [
            'pool_id' => $pool->getId(),
            'pool_name' => $pool->getName(),
            'period_id' => $periodId,
            'method' => $pool->getAllocationMethod()->value,
        ]);

        // Validate pool is active
        if (!$pool->isActive()) {
            throw new \InvalidArgumentException(
                sprintf('Cost pool %s is not active', $pool->getId())
            );
        }

        // Get allocation rules
        $rules = $pool->getAllocationRules();
        
        if (empty($rules)) {
            throw new InvalidAllocationRuleException(
                $pool->getId(),
                'No allocation rules defined for this pool'
            );
        }

        // Validate allocation rules
        $validation = $this->validateAllocationRules($pool);
        if (!$validation['valid']) {
            throw new InvalidAllocationRuleException(
                $pool->getId(),
                $validation['message']
            );
        }

        // Check for circular dependencies
        foreach ($rules as $rule) {
            if ($this->detectCircularDependencies($rule)) {
                throw new AllocationCycleDetectedException([
                    $pool->getId(),
                    $rule->getReceivingCostCenterId(),
                ]);
            }
        }

        // Execute allocation based on method
        $costCenterIds = array_map(
            fn($rule) => $rule->getReceivingCostCenterId(),
            $rules
        );
        
        $result = match ($pool->getAllocationMethod()) {
            AllocationMethod::Direct => $this->allocateDirect($pool, $rules),
            AllocationMethod::StepDown => $this->allocateStepDown($pool, $periodId, $costCenterIds),
            AllocationMethod::Reciprocal => $this->allocateReciprocal([$pool], $periodId),
            default => $this->allocateDirect($pool, $rules),
        };

        // Dispatch event
        $this->eventDispatcher->dispatch(new CostAllocatedEvent(
            poolId: $pool->getId(),
            poolName: $pool->getName(),
            allocations: $result['allocations'],
            totalAllocated: $result['total_allocated'],
            periodId: $periodId,
            tenantId: $pool->getTenantId(),
            occurredAt: new \DateTimeImmutable()
        ));

        $this->logger->info('Cost allocation completed', [
            'pool_id' => $pool->getId(),
            'total_allocated' => $result['total_allocated'],
            'allocation_count' => count($result['allocations']),
        ]);

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function validateAllocationRules(CostPool $pool): array
    {
        $rules = $pool->getAllocationRules();
        
        if (empty($rules)) {
            return [
                'valid' => false,
                'message' => 'No allocation rules defined',
            ];
        }

        // Check total ratio equals 1.0
        $totalRatio = 0.0;
        $activeRules = [];
        
        foreach ($rules as $rule) {
            if ($rule->isActive()) {
                $totalRatio += $rule->getAllocationRatio();
                $activeRules[] = $rule;
            }
        }

        if (abs($totalRatio - 1.0) >= 0.0001) {
            return [
                'valid' => false,
                'message' => sprintf(
                    'Allocation ratios must sum to 1.0, got %.4f',
                    $totalRatio
                ),
            ];
        }

        // Check for duplicate receiving cost centers
        $receivingCenters = [];
        foreach ($activeRules as $rule) {
            $centerId = $rule->getReceivingCostCenterId();
            if (isset($receivingCenters[$centerId])) {
                return [
                    'valid' => false,
                    'message' => sprintf(
                        'Duplicate allocation rule for cost center %s',
                        $centerId
                    ),
                ];
            }
            $receivingCenters[$centerId] = true;
        }

        return [
            'valid' => true,
            'message' => 'All allocation rules are valid',
            'active_rules' => count($activeRules),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function detectCircularDependencies(CostAllocationRule $rule): bool
    {
        // Build dependency graph and detect cycles using DFS
        $visited = [];
        $recursionStack = [];
        
        return $this->hasCycle($rule->getReceivingCostCenterId(), $visited, $recursionStack);
    }

    /**
     * {@inheritdoc}
     */
    public function calculateActivityRates(string $costCenterId, string $periodId): array
    {
        $this->logger->debug('Calculating activity rates', [
            'cost_center_id' => $costCenterId,
            'period_id' => $periodId,
        ]);

        // Get pools for cost center
        $pools = $this->costPoolQuery->findByCostCenter($costCenterId);
        
        $activityRates = [];
        
        foreach ($pools as $pool) {
            if (!$pool->isActive()) {
                continue;
            }

            $totalAmount = $pool->getTotalAmount();
            $rules = $pool->getAllocationRules();
            
            foreach ($rules as $rule) {
                if (!$rule->isActive()) {
                    continue;
                }

                $activityDriverId = $rule->getActivityDriverId();
                if ($activityDriverId !== null) {
                    // Calculate rate based on allocation
                    $allocatedAmount = $totalAmount * $rule->getAllocationRatio();
                    
                    // This is a simplified calculation - actual implementation
                    // would need actual activity quantities from manufacturing
                    $activityRates[] = [
                        'activity_driver_id' => $activityDriverId,
                        'cost_pool_id' => $pool->getId(),
                        'rate' => $allocatedAmount,
                        'unit' => 'currency',
                    ];
                }
            }
        }

        return $activityRates;
    }

    /**
     * {@inheritdoc}
     */
    public function allocateStepDown(CostPool $pool, string $periodId, array $order): array
    {
        $allocations = [];
        $remainingAmount = $pool->getTotalAmount();
        
        foreach ($order as $costCenterId) {
            $rules = $pool->getAllocationRules();
            $ruleForCenter = null;
            
            foreach ($rules as $rule) {
                if ($rule->getReceivingCostCenterId() === $costCenterId && $rule->isActive()) {
                    $ruleForCenter = $rule;
                    break;
                }
            }
            
            if ($ruleForCenter !== null) {
                $allocationAmount = $remainingAmount * $ruleForCenter->getAllocationRatio();
                $allocations[$costCenterId] = $allocationAmount;
                $remainingAmount -= $allocationAmount;
            }
        }

        $totalAllocated = array_sum($allocations);

        return [
            'allocations' => $allocations,
            'total_allocated' => $totalAllocated,
            'method' => 'step_down',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function allocateReciprocal(array $pools, string $periodId): array
    {
        // Simplified reciprocal allocation - uses iterative method
        $maxIterations = 100;
        $tolerance = 0.01;
        
        // Initialize allocations
        $allocations = [];
        $initialAmounts = [];
        
        foreach ($pools as $pool) {
            $initialAmounts[$pool->getId()] = $pool->getTotalAmount();
            $allocations[$pool->getId()] = [];
            
            foreach ($pool->getAllocationRules() as $rule) {
                if ($rule->isActive()) {
                    $allocations[$pool->getId()][$rule->getReceivingCostCenterId()] = 0.0;
                }
            }
        }
        
        // Iterative calculation
        for ($i = 0; $i < $maxIterations; $i++) {
            $previousAllocations = $allocations;
            
            foreach ($pools as $pool) {
                $rules = $pool->getAllocationRules();
                
                foreach ($rules as $rule) {
                    if (!$rule->isActive()) {
                        continue;
                    }
                    
                    $receivingId = $rule->getReceivingCostCenterId();
                    $baseAmount = $initialAmounts[$pool->getId()];
                    
                    // Add reciprocal amounts received from other pools
                    $reciprocalAmount = 0.0;
                    foreach ($allocations as $poolId => $poolAllocations) {
                        if (isset($poolAllocations[$pool->getId()])) {
                            $reciprocalAmount += $poolAllocations[$pool->getId()];
                        }
                    }
                    
                    $totalAmount = $baseAmount + $reciprocalAmount;
                    $allocations[$pool->getId()][$receivingId] = $totalAmount * $rule->getAllocationRatio();
                }
            }
            
            // Check convergence
            $diff = 0.0;
            foreach ($allocations as $poolId => $poolAllocations) {
                foreach ($poolAllocations as $centerId => $amount) {
                    $diff += abs($amount - ($previousAllocations[$poolId][$centerId] ?? 0));
                }
            }
            
            if ($diff < $tolerance) {
                break;
            }
        }
        
        // Flatten result
        $resultAllocations = [];
        $totalAllocated = 0.0;
        
        foreach ($allocations as $poolAllocations) {
            foreach ($poolAllocations as $costCenterId => $amount) {
                $totalAllocated += $amount;
                $resultAllocations[$costCenterId] = ($resultAllocations[$costCenterId] ?? 0.0) + $amount;
            }
        }

        return [
            'allocations' => $resultAllocations,
            'total_allocated' => $totalAllocated,
            'method' => 'reciprocal',
        ];
    }

    /**
     * Allocate using direct method
     */
    private function allocateDirect(CostPool $pool, array $rules): array
    {
        $allocations = [];
        $totalAmount = $pool->getTotalAmount();
        
        // Check for sufficient pool balance
        $totalRatio = 0.0;
        
        foreach ($rules as $rule) {
            if ($rule->isActive()) {
                $totalRatio += $rule->getAllocationRatio();
            }
        }
        
        $expectedTotal = $totalAmount * $totalRatio;
        
        if ($totalAmount < $expectedTotal - 0.01) {
            throw new InsufficientCostPoolException(
                $pool->getId(),
                $totalAmount,
                $expectedTotal
            );
        }
        
        // Calculate allocations
        foreach ($rules as $rule) {
            if ($rule->isActive()) {
                $allocationAmount = $totalAmount * $rule->getAllocationRatio();
                $allocations[$rule->getReceivingCostCenterId()] = $allocationAmount;
            }
        }
        
        return [
            'allocations' => $allocations,
            'total_allocated' => array_sum($allocations),
            'method' => 'direct',
        ];
    }

    /**
     * Detect cycle using DFS
     */
    private function hasCycle(string $nodeId, array &$visited, array &$recursionStack): bool
    {
        if (isset($recursionStack[$nodeId])) {
            return true;
        }
        
        if (isset($visited[$nodeId])) {
            return false;
        }
        
        $visited[$nodeId] = true;
        $recursionStack[$nodeId] = true;
        
        // Get cost pools for this center to check for dependencies
        $pools = $this->costPoolQuery->findByCostCenter($nodeId);
        
        foreach ($pools as $pool) {
            foreach ($pool->getAllocationRules() as $rule) {
                if ($this->hasCycle($rule->getReceivingCostCenterId(), $visited, $recursionStack)) {
                    return true;
                }
            }
        }
        
        unset($recursionStack[$nodeId]);
        
        return false;
    }
}
