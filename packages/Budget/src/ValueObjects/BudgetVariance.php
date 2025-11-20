<?php

declare(strict_types=1);

namespace Nexus\Budget\ValueObjects;

use Nexus\Finance\ValueObjects\Money;

/**
 * Budget Variance value object
 * 
 * Immutable representation of budget variance with revenue budget logic.
 */
final readonly class BudgetVariance
{
    public function __construct(
        private Money $allocated,
        private Money $committed,
        private Money $actual,
        private Money $available,
        private Money $variance,
        private float $variancePercentage,
        private bool $isRevenueBudget = false
    ) {}

    public function getAllocated(): Money
    {
        return $this->allocated;
    }

    public function getCommitted(): Money
    {
        return $this->committed;
    }

    public function getActual(): Money
    {
        return $this->actual;
    }

    public function getAvailable(): Money
    {
        return $this->available;
    }

    public function getVariance(): Money
    {
        return $this->variance;
    }

    public function getVariancePercentage(): float
    {
        return $this->variancePercentage;
    }

    public function isRevenueBudget(): bool
    {
        return $this->isRevenueBudget;
    }

    /**
     * Check if budget is over-spent (or under-earned for revenue budgets)
     */
    public function isOverBudget(): bool
    {
        if ($this->isRevenueBudget) {
            // For revenue: over budget means actual < allocated (underperforming)
            return $this->actual->isLessThan($this->allocated);
        }

        // For expense: over budget means actual > allocated
        return $this->actual->isGreaterThan($this->allocated);
    }

    /**
     * Check if budget is under-spent (or over-earned for revenue budgets)
     */
    public function isUnderBudget(): bool
    {
        if ($this->isRevenueBudget) {
            // For revenue: under budget means actual > allocated (overperforming)
            return $this->actual->isGreaterThan($this->allocated);
        }

        // For expense: under budget means actual < allocated
        return $this->actual->isLessThan($this->allocated);
    }

    /**
     * Get absolute variance (always positive)
     */
    public function getAbsoluteVariance(): Money
    {
        $amount = abs($this->variance->getAmount());
        return Money::of($amount, $this->variance->getCurrency());
    }

    /**
     * Check if variance requires investigation
     * 
     * @param float $threshold Investigation threshold percentage
     */
    public function requiresInvestigation(float $threshold = 15.0): bool
    {
        return abs($this->variancePercentage) > $threshold;
    }

    /**
     * Get variance status message
     */
    public function getStatusMessage(): string
    {
        if ($this->isRevenueBudget) {
            if ($this->isOverBudget()) {
                return sprintf(
                    'Revenue shortfall: %s (%.2f%% under target)',
                    $this->getAbsoluteVariance()->format(),
                    abs($this->variancePercentage)
                );
            }
            return sprintf(
                'Revenue surplus: %s (%.2f%% over target)',
                $this->getAbsoluteVariance()->format(),
                abs($this->variancePercentage)
            );
        }

        if ($this->isOverBudget()) {
            return sprintf(
                'Over budget: %s (%.2f%%)',
                $this->getAbsoluteVariance()->format(),
                abs($this->variancePercentage)
            );
        }

        return sprintf(
            'Under budget: %s (%.2f%%)',
            $this->getAbsoluteVariance()->format(),
            abs($this->variancePercentage)
        );
    }
}
