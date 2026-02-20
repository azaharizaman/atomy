<?php

declare(strict_types=1);

namespace Nexus\CostAccounting\Contracts;

/**
 * Activity Rate Interface
 * 
 * Defines operations for activity-based costing (ABC) rate management.
 */
interface ActivityRateInterface
{
    /**
     * Get the unique identifier
     */
    public function getId(): string;

    /**
     * Get the activity ID
     */
    public function getActivityId(): string;

    /**
     * Get the activity name
     */
    public function getActivityName(): string;

    /**
     * Get the cost pool ID
     */
    public function getCostPoolId(): string;

    /**
     * Get the cost center ID
     */
    public function getCostCenterId(): string;

    /**
     * Get the rate amount (cost per activity unit)
     */
    public function getRate(): float;

    /**
     * Get the unit of measure
     */
    public function getUnitOfMeasure(): string;

    /**
     * Get the estimated activity quantity
     */
    public function getEstimatedQuantity(): float;

    /**
     * Get the budgeted amount
     */
    public function getBudgetedAmount(): float;

    /**
     * Get the period ID
     */
    public function getPeriodId(): string;

    /**
     * Get the tenant ID
     */
    public function getTenantId(): string;

    /**
     * Check if this is an active rate
     */
    public function isActive(): bool;
}
