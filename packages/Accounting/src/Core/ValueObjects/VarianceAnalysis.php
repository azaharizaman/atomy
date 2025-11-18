<?php

declare(strict_types=1);

namespace Nexus\Accounting\Core\ValueObjects;

/**
 * Budget vs actual variance analysis.
 *
 * Calculates and stores variance information.
 */
final readonly class VarianceAnalysis
{
    public function __construct(
        private string $accountId,
        private string $accountName,
        private float $actualAmount,
        private float $budgetAmount,
        private ReportingPeriod $period,
        private ?string $notes = null
    ) {}

    public function getAccountId(): string
    {
        return $this->accountId;
    }

    public function getAccountName(): string
    {
        return $this->accountName;
    }

    public function getActualAmount(): float
    {
        return $this->actualAmount;
    }

    public function getBudgetAmount(): float
    {
        return $this->budgetAmount;
    }

    public function getPeriod(): ReportingPeriod
    {
        return $this->period;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    /**
     * Calculate variance amount (Actual - Budget).
     */
    public function getVarianceAmount(): float
    {
        return $this->actualAmount - $this->budgetAmount;
    }

    /**
     * Calculate variance percentage.
     */
    public function getVariancePercentage(): float
    {
        if ($this->budgetAmount == 0) {
            return $this->actualAmount > 0 ? 100.0 : 0.0;
        }

        return (($this->actualAmount - $this->budgetAmount) / abs($this->budgetAmount)) * 100;
    }

    /**
     * Check if variance is favorable (for revenue/income accounts).
     */
    public function isFavorable(bool $isRevenueAccount = true): bool
    {
        $variance = $this->getVarianceAmount();
        
        return $isRevenueAccount ? $variance > 0 : $variance < 0;
    }

    /**
     * Check if variance is significant (beyond threshold).
     */
    public function isSignificant(float $thresholdPercentage = 10.0): bool
    {
        return abs($this->getVariancePercentage()) >= $thresholdPercentage;
    }

    /**
     * Get variance status.
     */
    public function getStatus(bool $isRevenueAccount = true, float $threshold = 10.0): string
    {
        if (!$this->isSignificant($threshold)) {
            return 'within-budget';
        }

        return $this->isFavorable($isRevenueAccount) ? 'favorable' : 'unfavorable';
    }

    /**
     * Format variance for display.
     */
    public function formatVariance(): string
    {
        $amount = $this->getVarianceAmount();
        $percentage = $this->getVariancePercentage();
        $sign = $amount >= 0 ? '+' : '';
        
        return sprintf('%s%.2f (%.1f%%)', $sign, $amount, $percentage);
    }

    /**
     * Convert to array representation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'account_id' => $this->accountId,
            'account_name' => $this->accountName,
            'actual_amount' => $this->actualAmount,
            'budget_amount' => $this->budgetAmount,
            'variance_amount' => $this->getVarianceAmount(),
            'variance_percentage' => $this->getVariancePercentage(),
            'period' => $this->period->toArray(),
            'notes' => $this->notes,
        ];
    }
}
