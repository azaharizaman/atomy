<?php

declare(strict_types=1);

namespace Nexus\FixedAssetDepreciation\ValueObjects;

/**
 * Immutable value object representing a revaluation amount.
 *
 * This value object tracks the revaluation delta and provides
 * methods for calculating revaluation impacts.
 *
 * @package Nexus\FixedAssetDepreciation\ValueObjects
 */
final readonly class RevaluationAmount
{
    /**
     * @param float $amount The revaluation amount (positive for increment, negative for decrement)
     * @param string $currency The currency code
     * @param float $previousValue The value before revaluation
     * @param float $newValue The value after revaluation
     * @param float $depreciationImpact Impact on future depreciation
     */
    public function __construct(
        public float $amount,
        public string $currency,
        public float $previousValue,
        public float $newValue,
        public float $depreciationImpact,
    ) {}

    /**
     * Create from previous and new values.
     *
     * @param float $previousValue The previous value
     * @param float $newValue The new value
     * @param string $currency The currency code
     * @param float $depreciationImpact Impact on future depreciation
     * @return self
     */
    public static function fromValues(
        float $previousValue,
        float $newValue,
        string $currency = 'USD',
        float $depreciationImpact = 0.0
    ): self {
        return new self(
            amount: $newValue - $previousValue,
            currency: $currency,
            previousValue: $previousValue,
            newValue: $newValue,
            depreciationImpact: $depreciationImpact,
        );
    }

    /**
     * Create an increment (increase in value) revaluation amount.
     *
     * @param float $previousCost Previous asset cost
     * @param float $newCost New asset cost
     * @param float $previousSalvageValue Previous salvage value
     * @param float $newSalvageValue New salvage value
     * @param float $previousAccumulatedDepreciation Accumulated depreciation
     * @param string $currency Currency code
     * @return self
     */
    public static function createIncrement(
        float $previousCost,
        float $newCost,
        float $previousSalvageValue,
        float $newSalvageValue,
        float $previousAccumulatedDepreciation,
        string $currency = 'USD'
    ): self {
        $previousNetBookValue = $previousCost - $previousAccumulatedDepreciation;
        $newNetBookValue = $newCost - $previousAccumulatedDepreciation;
        
        // Calculate depreciation impact: new depreciable base vs old
        $previousDepreciableBase = $previousCost - $previousSalvageValue;
        $newDepreciableBase = $newCost - $newSalvageValue;
        $depreciationImpact = $newDepreciableBase - $previousDepreciableBase;

        return self::fromValues(
            $previousNetBookValue,
            $newNetBookValue,
            $currency,
            $depreciationImpact
        );
    }

    /**
     * Create a decrement (decrease in value) revaluation amount.
     *
     * @param float $previousCost Previous asset cost
     * @param float $newCost New asset cost
     * @param float $previousSalvageValue Previous salvage value
     * @param float $newSalvageValue New salvage value
     * @param float $previousAccumulatedDepreciation Accumulated depreciation
     * @param string $currency Currency code
     * @return self
     */
    public static function createDecrement(
        float $previousCost,
        float $newCost,
        float $previousSalvageValue,
        float $newSalvageValue,
        float $previousAccumulatedDepreciation,
        string $currency = 'USD'
    ): self {
        return self::createIncrement(
            $previousCost,
            $newCost,
            $previousSalvageValue,
            $newSalvageValue,
            $previousAccumulatedDepreciation,
            $currency
        );
    }

    /**
     * Check if this is an increment (increase in value).
     *
     * @return bool True if amount is positive
     */
    public function isIncrement(): bool
    {
        return $this->amount > 0;
    }

    /**
     * Check if this is a decrement (decrease in value).
     *
     * @return bool True if amount is negative
     */
    public function isDecrement(): bool
    {
        return $this->amount < 0;
    }

    /**
     * Get the absolute amount.
     *
     * @return float The absolute value of the revaluation
     */
    public function getAbsoluteAmount(): float
    {
        return abs($this->amount);
    }

    /**
     * Get the percentage change.
     *
     * @return float The percentage change (e.g., 0.10 for 10%)
     */
    public function getPercentageChange(): float
    {
        if ($this->previousValue === 0.0) {
            return $this->amount > 0 ? 1.0 : 0.0;
        }
        return $this->amount / $this->previousValue;
    }

    /**
     * Get the depreciation change per period.
     *
     * @param int $remainingPeriods Number of remaining periods
     * @return float Change in depreciation per period
     */
    public function getDepreciationChangePerPeriod(int $remainingPeriods): float
    {
        if ($remainingPeriods <= 0) {
            return 0.0;
        }
        return $this->depreciationImpact / $remainingPeriods;
    }

    /**
     * Add another revaluation amount.
     *
     * @param RevaluationAmount $other The other revaluation amount
     * @return self New instance with combined amount
     * @throws \InvalidArgumentException If currencies don't match
     */
    public function add(self $other): self
    {
        if ($other->currency !== $this->currency) {
            throw new \InvalidArgumentException('Cannot add revaluation amounts with different currencies');
        }

        return new self(
            amount: $this->amount + $other->amount,
            currency: $this->currency,
            previousValue: $this->previousValue,
            newValue: $this->newValue + $other->amount,
            depreciationImpact: $this->depreciationImpact + $other->depreciationImpact,
        );
    }

    /**
     * Negate the revaluation amount.
     *
     * @return self New instance with negated amount
     */
    public function negate(): self
    {
        return new self(
            amount: -$this->amount,
            currency: $this->currency,
            previousValue: $this->newValue,
            newValue: $this->previousValue,
            depreciationImpact: -$this->depreciationImpact,
        );
    }

    /**
     * Scale the revaluation amount by a factor.
     *
     * @param float $factor The factor to multiply by
     * @return self New instance with scaled amount
     */
    public function multiply(float $factor): self
    {
        return new self(
            amount: $this->amount * $factor,
            currency: $this->currency,
            previousValue: $this->previousValue,
            newValue: $this->previousValue + ($this->amount * $factor),
            depreciationImpact: $this->depreciationImpact * $factor,
        );
    }

    /**
     * Get the revaluation reserve impact.
     *
     * For IFRS, increments go to revaluation reserve (equity).
     *
     * @return float The amount to record as revaluation reserve
     */
    public function getRevaluationReserveImpact(): float
    {
        return $this->isIncrement() ? $this->amount : 0.0;
    }

    /**
     * Get the expense impact.
     *
     * For decrements, the amount may be expensed or offset
     * against revaluation reserve.
     *
     * @param float $availableReserve Available revaluation reserve to offset
     * @return array{
     *     expense: float,
     *     offsetFromReserve: float
     * }
     */
    public function getExpenseImpact(float $availableReserve): array
    {
        if (!$this->isDecrement()) {
            return ['expense' => 0.0, 'offsetFromReserve' => 0.0];
        }

        $absAmount = $this->getAbsoluteAmount();
        $offsetFromReserve = min($absAmount, $availableReserve);
        $expense = $absAmount - $offsetFromReserve;

        return [
            'expense' => $expense,
            'offsetFromReserve' => $offsetFromReserve,
        ];
    }

    /**
     * Format as string.
     *
     * @return string Formatted string representation
     */
    public function format(): string
    {
        $formatted = number_format($this->amount, 2);
        $sign = $this->amount >= 0 ? '+' : '';
        return sprintf(
            '%s%s %s (Previous: %s, New: %s)',
            $sign,
            $formatted,
            $this->currency,
            number_format($this->previousValue, 2),
            number_format($this->newValue, 2)
        );
    }

    /**
     * Convert to array.
     *
     * @return array{
     *     amount: float,
     *     currency: string,
     *     previousValue: float,
     *     newValue: float,
     *     depreciationImpact: float,
     *     isIncrement: bool,
     *     percentageChange: float
     * }
     */
    public function toArray(): array
    {
        return [
            'amount' => $this->amount,
            'currency' => $this->currency,
            'previousValue' => $this->previousValue,
            'newValue' => $this->newValue,
            'depreciationImpact' => $this->depreciationImpact,
            'isIncrement' => $this->isIncrement(),
            'percentageChange' => $this->getPercentageChange(),
        ];
    }
}
