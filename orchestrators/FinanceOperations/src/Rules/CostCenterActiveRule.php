<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Rules;

use Nexus\FinanceOperations\Contracts\RuleInterface;
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
     * @param object $costCenterQuery CostCenterQueryInterface for cost center lookup
     */
    public function __construct(
        private object $costCenterQuery,
    ) {}

    /**
     * @inheritDoc
     *
     * @param object $context Context containing tenantId and costCenterIds
     * @return RuleResult The rule check result
     */
    public function check(object $context): RuleResult
    {
        $tenantId = $this->extractTenantId($context);
        $costCenterIds = $this->extractCostCenterIds($context);

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

            if (!$this->isCostCenterActive($costCenter)) {
                $violations[] = [
                    'type' => 'inactive',
                    'cost_center_id' => $costCenterId,
                    'cost_center_name' => $this->getCostCenterName($costCenter),
                    'message' => sprintf(
                        'Cost center "%s" (%s) is inactive',
                        $this->getCostCenterName($costCenter),
                        $costCenterId
                    ),
                ];
            }

            // Check if cost center can receive allocations
            if (!$this->canReceiveAllocations($costCenter)) {
                $violations[] = [
                    'type' => 'cannot_receive',
                    'cost_center_id' => $costCenterId,
                    'cost_center_name' => $this->getCostCenterName($costCenter),
                    'message' => sprintf(
                        'Cost center "%s" cannot receive allocations',
                        $this->getCostCenterName($costCenter)
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

    /**
     * Extract tenant ID from context.
     *
     * @param object $context The context object
     * @return string The tenant ID
     */
    private function extractTenantId(object $context): string
    {
        if (method_exists($context, 'getTenantId')) {
            return $context->getTenantId();
        }

        if (property_exists($context, 'tenantId')) {
            return $context->tenantId ?? '';
        }

        if (property_exists($context, 'tenant_id')) {
            return $context->tenant_id ?? '';
        }

        return '';
    }

    /**
     * Extract cost center IDs from context.
     *
     * @param object $context The context object
     * @return array<string> The cost center IDs
     */
    private function extractCostCenterIds(object $context): array
    {
        if (method_exists($context, 'getCostCenterIds')) {
            return $context->getCostCenterIds();
        }

        if (property_exists($context, 'costCenterIds')) {
            return $context->costCenterIds ?? [];
        }

        if (property_exists($context, 'cost_center_ids')) {
            return $context->cost_center_ids ?? [];
        }

        // Single cost center ID
        $singleId = $this->extractSingleCostCenterId($context);
        if ($singleId !== null) {
            return [$singleId];
        }

        return [];
    }

    /**
     * Extract a single cost center ID from context.
     *
     * @param object $context The context object
     * @return string|null The cost center ID or null
     */
    private function extractSingleCostCenterId(object $context): ?string
    {
        if (method_exists($context, 'getCostCenterId')) {
            return $context->getCostCenterId();
        }

        if (property_exists($context, 'costCenterId')) {
            return $context->costCenterId ?? null;
        }

        if (property_exists($context, 'cost_center_id')) {
            return $context->cost_center_id ?? null;
        }

        return null;
    }

    /**
     * Check if the cost center is active.
     *
     * @param object $costCenter The cost center object
     * @return bool True if the cost center is active
     */
    private function isCostCenterActive(object $costCenter): bool
    {
        if (method_exists($costCenter, 'isActive')) {
            return $costCenter->isActive();
        }

        if (method_exists($costCenter, 'getIsActive')) {
            return $costCenter->getIsActive();
        }

        if (property_exists($costCenter, 'isActive')) {
            return $costCenter->isActive;
        }

        if (property_exists($costCenter, 'is_active')) {
            return $costCenter->is_active;
        }

        // Default to true if we cannot determine status
        return true;
    }

    /**
     * Get the cost center name.
     *
     * @param object $costCenter The cost center object
     * @return string The cost center name
     */
    private function getCostCenterName(object $costCenter): string
    {
        if (method_exists($costCenter, 'getName')) {
            return $costCenter->getName();
        }

        if (property_exists($costCenter, 'name')) {
            return $costCenter->name ?? '';
        }

        return '';
    }

    /**
     * Check if the cost center can receive allocations.
     *
     * @param object $costCenter The cost center object
     * @return bool True if the cost center can receive allocations
     */
    private function canReceiveAllocations(object $costCenter): bool
    {
        if (method_exists($costCenter, 'canReceiveAllocations')) {
            return $costCenter->canReceiveAllocations();
        }

        if (method_exists($costCenter, 'getCanReceiveAllocations')) {
            return $costCenter->getCanReceiveAllocations();
        }

        if (property_exists($costCenter, 'canReceiveAllocations')) {
            return $costCenter->canReceiveAllocations;
        }

        if (property_exists($costCenter, 'can_receive_allocations')) {
            return $costCenter->can_receive_allocations;
        }

        // Default to true if we cannot determine capability
        return true;
    }
}