<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Rules;

use Nexus\FinanceOperations\Contracts\CostCenterQueryInterface;
use Nexus\FinanceOperations\Contracts\RuleInterface;
use Nexus\FinanceOperations\Contracts\RuleContextInterface;
use Nexus\FinanceOperations\DTOs\RuleResult;

/**
 * Rule to validate that cost centers are active for allocation.
 *
 * This rule ensures that all target cost centers in an allocation
 * are active and can receive cost allocations.
 *
 * Following Advanced Orchestrator Pattern:
 * - Single responsibility: Cost center active status validation
 * - Testable in isolation
 * - Reusable across coordinators
 *
 * @see ARCHITECTURE.md Section 4 for rule patterns
 * @since 1.0.0
 */
final readonly class CostCenterActiveRule implements RuleInterface
{
    /**
     * @param CostCenterQueryInterface $costCenterQuery Cost center lookup contract
     */
    public function __construct(
        private CostCenterQueryInterface $costCenterQuery,
    ) {}

    /**
     * @inheritDoc
     *
     * @param RuleContextInterface $context Context containing tenantId and costCenterIds
     * @return RuleResult The rule check result
     */
    public function check(RuleContextInterface $context): RuleResult
    {
        $tenantId = $context->getTenantId();
        $costCenterIds = $context->getCostCenterIds();
        if ($costCenterIds === []) {
            $singleId = $context->getCostCenterId();
            if ($singleId !== null && $singleId !== '') {
                $costCenterIds = [$singleId];
            }
        }

        if (empty($costCenterIds)) {
            return RuleResult::passed($this->getName());
        }

        $violations = [];

        foreach ($costCenterIds as $costCenterId) {
            $costCenter = $this->costCenterQuery->find($tenantId, $costCenterId);

            if ($costCenter === null) {
                $violations[] = [
                    'type' => 'not_found',
                    'cost_center_id' => $costCenterId,
                    'message' => sprintf('Cost center %s not found', $costCenterId),
                ];
                continue;
            }

            if (!$costCenter->isActive()) {
                $violations[] = [
                    'type' => 'inactive',
                    'cost_center_id' => $costCenterId,
                    'cost_center_name' => $costCenter->getName(),
                    'message' => sprintf(
                        'Cost center "%s" (%s) is inactive',
                        $costCenter->getName(),
                        $costCenterId
                    ),
                ];
            }

            // Check if cost center can receive allocations
            if (!$costCenter->canReceiveAllocations()) {
                $violations[] = [
                    'type' => 'cannot_receive',
                    'cost_center_id' => $costCenterId,
                    'cost_center_name' => $costCenter->getName(),
                    'message' => sprintf(
                        'Cost center "%s" cannot receive allocations',
                        $costCenter->getName()
                    ),
                ];
            }
        }

        if (!empty($violations)) {
            return RuleResult::failed(
                $this->getName(),
                sprintf(
                    'Cost center validation failed with %d violations',
                    count($violations)
                ),
                $violations
            );
        }

        return RuleResult::passed($this->getName());
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return 'cost_center_active';
    }
}