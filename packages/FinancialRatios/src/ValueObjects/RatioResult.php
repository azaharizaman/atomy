<?php

declare(strict_types=1);

namespace Nexus\FinancialRatios\ValueObjects;

use Nexus\FinancialRatios\Enums\RatioCategory;

/**
 * Represents the result of a ratio calculation.
 */
final readonly class RatioResult
{
    public function __construct(
        public string $ratioName,
        public float $value,
        public RatioCategory $category,
        public ?float $benchmark = null,
        public ?string $interpretation = null,
        public ?\DateTimeImmutable $calculatedAt = null,
    ) {}

    /**
     * Check if the ratio is above benchmark.
     */
    public function isAboveBenchmark(): bool
    {
        if ($this->benchmark === null) {
            return false;
        }

        return $this->value > $this->benchmark;
    }

    /**
     * Get variance from benchmark as percentage.
     */
    public function getBenchmarkVariance(): ?float
    {
        if ($this->benchmark === null || $this->benchmark === 0.0) {
            return null;
        }

        return (($this->value - $this->benchmark) / $this->benchmark) * 100;
    }

    /**
     * Format the ratio value as percentage.
     */
    public function asPercentage(int $decimals = 2): string
    {
        return number_format($this->value * 100, $decimals) . '%';
    }

    /**
     * Format the ratio value with specified decimals.
     */
    public function formatted(int $decimals = 2): string
    {
        return number_format($this->value, $decimals);
    }
}
