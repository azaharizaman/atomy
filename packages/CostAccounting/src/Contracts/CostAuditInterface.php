<?php

declare(strict_types=1);

namespace Nexus\CostAccounting\Contracts;

/**
 * Cost Audit Interface
 * 
 * Defines operations for cost accounting audit trail.
 */
interface CostAuditInterface
{
    /**
     * Log a cost center change
     */
    public function logCostCenterChange(
        string $costCenterId,
        string $action,
        array $changes,
        string $userId
    ): void;

    /**
     * Log a cost pool change
     */
    public function logCostPoolChange(
        string $poolId,
        string $action,
        array $changes,
        string $userId
    ): void;

    /**
     * Log a cost allocation
     */
    public function logCostAllocation(
        string $poolId,
        array $allocations,
        string $userId
    ): void;

    /**
     * Log a product cost calculation
     */
    public function logProductCostCalculation(
        string $productId,
        string $periodId,
        array $costs,
        string $userId
    ): void;

    /**
     * Log a variance calculation
     */
    public function logVarianceCalculation(
        string $productId,
        string $periodId,
        array $variances,
        string $userId
    ): void;

    /**
     * Get audit trail for a cost center
     */
    public function getCostCenterAuditTrail(
        string $costCenterId,
        ?\DateTimeImmutable $from = null,
        ?\DateTimeImmutable $to = null
    ): array;

    /**
     * Get audit trail for a product
     */
    public function getProductCostAuditTrail(
        string $productId,
        ?\DateTimeImmutable $from = null,
        ?\DateTimeImmutable $to = null
    ): array;
}
