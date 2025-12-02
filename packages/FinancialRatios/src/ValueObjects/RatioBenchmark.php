<?php

declare(strict_types=1);

namespace Nexus\FinancialRatios\ValueObjects;

use Nexus\FinancialRatios\Enums\RatioCategory;

/**
 * Represents a benchmark for a specific ratio.
 */
final readonly class RatioBenchmark
{
    public function __construct(
        public string $ratioName,
        public RatioCategory $category,
        public float $industryAverage,
        public float $industryMedian,
        public float $topQuartile,
        public float $bottomQuartile,
        public string $industryCode,
        public ?string $industryName = null,
        public ?\DateTimeImmutable $asOfDate = null,
    ) {}

    /**
     * Check if a value is in the top quartile.
     */
    public function isTopQuartile(float $value): bool
    {
        return $value >= $this->topQuartile;
    }

    /**
     * Check if a value is in the bottom quartile.
     */
    public function isBottomQuartile(float $value): bool
    {
        return $value <= $this->bottomQuartile;
    }

    /**
     * Check if a value is above industry average.
     */
    public function isAboveAverage(float $value): bool
    {
        return $value > $this->industryAverage;
    }

    /**
     * Get percentile position for a value (simplified).
     */
    public function getPercentilePosition(float $value): string
    {
        if ($value >= $this->topQuartile) {
            return 'Top 25%';
        }

        if ($value >= $this->industryMedian) {
            return 'Above Median';
        }

        if ($value >= $this->bottomQuartile) {
            return 'Below Median';
        }

        return 'Bottom 25%';
    }
}
