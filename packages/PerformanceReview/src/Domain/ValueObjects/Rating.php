<?php

declare(strict_types=1);

namespace Nexus\PerformanceReview\Domain\ValueObjects;

use InvalidArgumentException;

final readonly class Rating
{
    public function __construct(
        public float $value,
        public float $max = 5.0
    ) {
        if ($value < 0) {
            throw new InvalidArgumentException("Rating value cannot be negative. Given: {$value}");
        }

        if ($value > $max) {
            throw new InvalidArgumentException("Rating value cannot exceed maximum of {$max}. Given: {$value}");
        }
    }

    public function toPercentage(): float
    {
        if ($this->max === 0.0) {
            return 0.0;
        }

        return ($this->value / $this->max) * 100.0;
    }

    public function isPassing(float $threshold = 3.0): bool
    {
        return $this->value >= $threshold;
    }

    public function format(): string
    {
        return sprintf('%.1f / %.1f', $this->value, $this->max);
    }
}
