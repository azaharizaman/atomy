<?php

declare(strict_types=1);

namespace Nexus\Common\ValueObjects;

use Nexus\Common\Contracts\Addable;
use Nexus\Common\Contracts\Comparable;
use Nexus\Common\Contracts\Divisible;
use Nexus\Common\Contracts\Multipliable;
use Nexus\Common\Contracts\SerializableVO;
use Nexus\Common\Contracts\Statistical;
use Nexus\Common\Contracts\Subtractable;
use Nexus\Common\Contracts\TrendAnalyzable;
use Nexus\Common\Exceptions\InvalidValueException;

/**
 * Immutable variance result value object with statistical and trend analysis.
 * 
 * Used for financial reporting, budget variance, performance analysis.
 * Most complex VO - implements all analysis interfaces.
 */
final readonly class VarianceResult implements
    Comparable,
    Addable,
    Subtractable,
    Multipliable,
    Divisible,
    Statistical,
    TrendAnalyzable,
    SerializableVO
{
    private float $variance;
    private float $percentageVariance;

    /**
     * @param float $actual Actual value
     * @param float $budget Budget/expected value
     * @throws InvalidValueException
     */
    public function __construct(
        private float $actual,
        private float $budget
    ) {
        if ($budget < 0 && $actual < 0) {
            throw new InvalidValueException('Both actual and budget cannot be negative');
        }

        $this->variance = $actual - $budget;
        $this->percentageVariance = $budget != 0 
            ? (($actual - $budget) / abs($budget)) * 100 
            : 0;
    }

    public function getActual(): float
    {
        return $this->actual;
    }

    public function getBudget(): float
    {
        return $this->budget;
    }

    public function getVariance(): float
    {
        return $this->variance;
    }

    public function getPercentageVariance(): float
    {
        return $this->percentageVariance;
    }

    public function isFavorable(): bool
    {
        // For revenue/income: actual > budget is favorable
        // For expenses: actual < budget is favorable
        // This method assumes revenue context by default
        return $this->variance > 0;
    }

    public function isUnfavorable(): bool
    {
        return !$this->isFavorable() && $this->variance != 0;
    }

    // Statistical implementation
    public static function average(array $values): static
    {
        if (empty($values)) {
            throw new InvalidValueException('Cannot calculate average of empty array');
        }

        $totalActual = 0;
        $totalBudget = 0;

        foreach ($values as $result) {
            if (!$result instanceof self) {
                throw new InvalidValueException('All values must be VarianceResult instances');
            }
            $totalActual += $result->actual;
            $totalBudget += $result->budget;
        }

        $count = count($values);
        return new self($totalActual / $count, $totalBudget / $count);
    }

    public function abs(): static
    {
        return new self(abs($this->actual), abs($this->budget));
    }

    public function isWithinRange(Statistical $min, Statistical $max): bool
    {
        if (!$min instanceof self || !$max instanceof self) {
            throw new InvalidValueException('Min and max must be VarianceResult instances');
        }

        return $this->actual >= $min->actual && $this->actual <= $max->actual;
    }

    // TrendAnalyzable implementation
    public function getTrendDirection(TrendAnalyzable $previous): string
    {
        if (!$previous instanceof self) {
            throw new InvalidValueException('Previous value must be VarianceResult');
        }

        if ($this->actual > $previous->actual) {
            return 'up';
        } elseif ($this->actual < $previous->actual) {
            return 'down';
        } else {
            return 'stable';
        }
    }

    public function percentageChange(TrendAnalyzable $previous): float
    {
        if (!$previous instanceof self) {
            throw new InvalidValueException('Previous value must be VarianceResult');
        }

        if ($previous->actual == 0) {
            return 0;
        }

        return (($this->actual - $previous->actual) / abs($previous->actual)) * 100;
    }

    public function isSignificantChange(TrendAnalyzable $previous, float $threshold): bool
    {
        return abs($this->percentageChange($previous)) >= $threshold;
    }

    // Comparable implementation
    public function compareTo(Comparable $other): int
    {
        if (!$other instanceof self) {
            throw new \InvalidArgumentException('Can only compare with another VarianceResult');
        }

        // Compare by variance magnitude
        return $this->variance <=> $other->variance;
    }

    public function equals(Comparable $other): bool
    {
        if (!$other instanceof self) {
            return false;
        }

        return $this->actual === $other->actual && $this->budget === $other->budget;
    }

    public function greaterThan(Comparable $other): bool
    {
        return $this->compareTo($other) > 0;
    }

    public function lessThan(Comparable $other): bool
    {
        return $this->compareTo($other) < 0;
    }

    // Addable implementation
    public function add(Addable $other): static
    {
        if (!$other instanceof self) {
            throw new \InvalidArgumentException('Can only add another VarianceResult');
        }

        return new self(
            $this->actual + $other->actual,
            $this->budget + $other->budget
        );
    }

    // Subtractable implementation
    public function subtract(Subtractable $other): static
    {
        if (!$other instanceof self) {
            throw new \InvalidArgumentException('Can only subtract another VarianceResult');
        }

        return new self(
            $this->actual - $other->actual,
            $this->budget - $other->budget
        );
    }

    // Multipliable implementation
    public function multiply(float|int $multiplier): static
    {
        return new self(
            $this->actual * $multiplier,
            $this->budget * $multiplier
        );
    }

    // Divisible implementation
    public function divide(float|int $divisor): static
    {
        if ($divisor == 0) {
            throw new InvalidValueException('Cannot divide by zero');
        }

        return new self(
            $this->actual / $divisor,
            $this->budget / $divisor
        );
    }

    // SerializableVO implementation
    public function toArray(): array
    {
        return [
            'actual' => $this->actual,
            'budget' => $this->budget,
            'variance' => $this->variance,
            'percentage_variance' => $this->percentageVariance,
            'is_favorable' => $this->isFavorable(),
        ];
    }

    public function toString(): string
    {
        $favorability = $this->isFavorable() ? 'favorable' : 'unfavorable';
        return sprintf(
            'Actual: %.2f, Budget: %.2f, Variance: %.2f (%.2f%% %s)',
            $this->actual,
            $this->budget,
            $this->variance,
            $this->percentageVariance,
            $favorability
        );
    }

    public static function fromArray(array $data): static
    {
        return new self(
            actual: $data['actual'],
            budget: $data['budget']
        );
    }
}
