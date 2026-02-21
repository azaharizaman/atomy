<?php

declare(strict_types=1);

namespace Nexus\CostAccounting\Contracts;

/**
 * Cost Revaluation Interface
 * 
 * Defines operations for cost revaluation management.
 */
interface CostRevaluationInterface
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
     * Get the previous cost
     */
    public function getPreviousCost(): float;

    /**
     * Get the new (revalued) cost
     */
    public function getNewCost(): float;

    /**
     * Get the variance amount
     */
    public function getVarianceAmount(): float;

    /**
     * Get the revaluation percentage
     */
    public function getVariancePercentage(): float;

    /**
     * Get the revaluation date
     */
    public function getRevaluationDate(): \DateTimeImmutable;

    /**
     * Get the period ID
     */
    public function getPeriodId(): string;

    /**
     * Get the tenant ID
     */
    public function getTenantId(): string;

    /**
     * Get the reason for revaluation
     */
    public function getReason(): string;

    /**
     * Check if this is an increase (revaluation upward)
     */
    public function isIncrease(): bool;

    /**
     * Check if this is a decrease (revaluation downward)
     */
    public function isDecrease(): bool;
}
