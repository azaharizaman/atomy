<?php

declare(strict_types=1);

namespace Nexus\AccountVarianceAnalysis\ValueObjects;

use Nexus\AccountVarianceAnalysis\Enums\TrendDirection;

/**
 * Result of trend analysis.
 */
final readonly class TrendData
{
    public function __construct(
        private TrendDirection $direction,
        private float $slope,
        private float $rSquared,
        private array $values,
        private ?float $forecast = null,
    ) {}

    public function getDirection(): TrendDirection
    {
        return $this->direction;
    }

    public function getSlope(): float
    {
        return $this->slope;
    }

    public function getRSquared(): float
    {
        return $this->rSquared;
    }

    public function getValues(): array
    {
        return $this->values;
    }

    public function getForecast(): ?float
    {
        return $this->forecast;
    }

    public function isSignificant(): bool
    {
        return $this->rSquared >= 0.7;
    }
}
