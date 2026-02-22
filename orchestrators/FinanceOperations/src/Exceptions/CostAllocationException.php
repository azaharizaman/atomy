<?php
declare(strict_types=1);

namespace Nexus\FinanceOperations\Exceptions;

use Throwable;

/**
 * Exception for cost allocation coordination errors.
 * 
 * Thrown when cost allocation operations fail, including:
 * - Cost pool allocation failures
 * - Product costing errors
 * - Periodic allocation issues
 * 
 * @since 1.0.0
 */
final class CostAllocationException extends CoordinationException
{
    /**
     * Create exception for cost allocation failure.
     */
    public static function allocationFailed(
        string $tenantId,
        string $costPoolId,
        string $reason,
        ?Throwable $previous = null
    ): self {
        return new self(
            sprintf('Cost allocation failed for pool %s: %s', $costPoolId, $reason),
            'CostAllocationCoordinator',
            ['tenant_id' => $tenantId, 'cost_pool_id' => $costPoolId],
            $previous
        );
    }

    /**
     * Create exception for invalid allocation method.
     */
    public static function invalidAllocationMethod(
        string $tenantId,
        string $method,
        array $validMethods
    ): self {
        return new self(
            sprintf('Invalid allocation method "%s". Valid methods: %s', 
                $method, implode(', ', $validMethods)),
            'CostAllocationCoordinator',
            [
                'tenant_id' => $tenantId,
                'method' => $method,
                'valid_methods' => $validMethods
            ]
        );
    }

    /**
     * Create exception for product costing failure.
     */
    public static function productCostingFailed(
        string $tenantId,
        string $productId,
        string $reason,
        ?Throwable $previous = null
    ): self {
        return new self(
            sprintf('Product costing failed for product %s: %s', $productId, $reason),
            'CostAllocationCoordinator',
            ['tenant_id' => $tenantId, 'product_id' => $productId],
            $previous
        );
    }

    /**
     * Create exception for inactive cost center.
     */
    public static function inactiveCostCenter(
        string $tenantId,
        string $costCenterId
    ): self {
        return new self(
            sprintf('Cost center %s is inactive and cannot receive allocations', $costCenterId),
            'CostAllocationCoordinator',
            ['tenant_id' => $tenantId, 'cost_center_id' => $costCenterId]
        );
    }

    /**
     * Create exception for BOM not found.
     */
    public static function bomNotFound(
        string $tenantId,
        string $productId
    ): self {
        return new self(
            sprintf('Bill of Materials not found for product %s', $productId),
            'CostAllocationCoordinator',
            ['tenant_id' => $tenantId, 'product_id' => $productId]
        );
    }

    /**
     * Create exception for allocation rule conflict.
     */
    public static function allocationRuleConflict(
        string $tenantId,
        string $ruleId,
        string $conflictDescription
    ): self {
        return new self(
            sprintf('Allocation rule %s has a conflict: %s', $ruleId, $conflictDescription),
            'CostAllocationCoordinator',
            ['tenant_id' => $tenantId, 'rule_id' => $ruleId]
        );
    }
}
