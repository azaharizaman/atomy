<?php

declare(strict_types=1);

namespace Nexus\Accounting\Domain\ValueObjects;

/**
 * Represents the result of a variance analysis.
 */
final readonly class VarianceAnalysis
{
    public function __construct(
        private string $accountCode,
        private string $accountName,
        private float $currentAmount,
        private float $comparisonAmount,
        private float $variance,
        private float $variancePercentage,
        private string $direction
    ) {}

    public function getAccountCode(): string
    {
        return $this->accountCode;
    }

    public function getAccountName(): string
    {
        return $this->accountName;
    }

    public function getCurrentAmount(): float
    {
        return $this->currentAmount;
    }

    public function getComparisonAmount(): float
    {
        return $this->comparisonAmount;
    }

    public function getVariance(): float
    {
        return $this->variance;
    }

    public function getVariancePercentage(): float
    {
        return $this->variancePercentage;
    }

    public function getDirection(): string
    {
        return $this->direction;
    }

    /**
     * Check if the variance is favorable.
     */
    public function isFavorable(): bool
    {
        return $this->direction === 'favorable';
    }

    /**
     * Check if the variance is unfavorable.
     */
    public function isUnfavorable(): bool
    {
        return $this->direction === 'unfavorable';
    }

    /**
     * Check if the variance exceeds a threshold.
     */
    public function exceedsThreshold(float $thresholdPercentage): bool
    {
        return abs($this->variancePercentage) > $thresholdPercentage;
    }

    /**
     * Create a variance analysis from two amounts.
     */
    public static function calculate(
        string $accountCode,
        string $accountName,
        float $currentAmount,
        float $comparisonAmount,
        bool $isCostAccount = false
    ): self {
        $variance = $currentAmount - $comparisonAmount;
        $variancePercentage = $comparisonAmount != 0
            ? ($variance / abs($comparisonAmount)) * 100
            : ($currentAmount != 0 ? 100.0 : 0.0);

        // For cost/expense accounts, a decrease is favorable
        // For revenue/income accounts, an increase is favorable
        if ($isCostAccount) {
            $direction = $variance < 0 ? 'favorable' : ($variance > 0 ? 'unfavorable' : 'unchanged');
        } else {
            $direction = $variance > 0 ? 'favorable' : ($variance < 0 ? 'unfavorable' : 'unchanged');
        }

        return new self(
            $accountCode,
            $accountName,
            $currentAmount,
            $comparisonAmount,
            $variance,
            $variancePercentage,
            $direction
        );
    }

    /**
     * Convert to array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'account_code' => $this->accountCode,
            'account_name' => $this->accountName,
            'current_amount' => $this->currentAmount,
            'comparison_amount' => $this->comparisonAmount,
            'variance' => $this->variance,
            'variance_percentage' => $this->variancePercentage,
            'direction' => $this->direction,
        ];
    }
}
