<?php

declare(strict_types=1);

namespace Nexus\CostAccounting\Contracts;

/**
 * Cost Variance Interface
 * 
 * Defines operations for cost variance tracking.
 */
interface CostVarianceInterface
{
    /**
     * Get the unique identifier
     */
    public function getId(): string;

    /**
     * Get the product ID
     */
    public function getProductId(): string;

    /**
     * Get the cost center ID
     */
    public function getCostCenterId(): string;

    /**
     * Get the period ID
     */
    public function getPeriodId(): string;

    /**
     * Get the total variance amount
     */
    public function getTotalVariance(): float;

    /**
     * Get the material variance
     */
    public function getMaterialVariance(): float;

    /**
     * Get the labor variance
     */
    public function getLaborVariance(): float;

    /**
     * Get the overhead variance
     */
    public function getOverheadVariance(): float;

    /**
     * Get the price variance
     */
    public function getPriceVariance(): float;

    /**
     * Get the rate variance
     */
    public function getRateVariance(): float;

    /**
     * Get the efficiency variance
     */
    public function getEfficiencyVariance(): float;

    /**
     * Check if variance is favorable (negative means lower costs)
     */
    public function isFavorable(): bool;

    /**
     * Check if variance is unfavorable (positive means higher costs)
     */
    public function isUnfavorable(): bool;

    /**
     * Get the tenant ID
     */
    public function getTenantId(): string;
}
