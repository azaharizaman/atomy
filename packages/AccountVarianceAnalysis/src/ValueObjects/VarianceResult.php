<?php

declare(strict_types=1);

namespace Nexus\AccountVarianceAnalysis\ValueObjects;

use Nexus\AccountVarianceAnalysis\Enums\VarianceType;

/**
 * Result of a variance calculation.
 */
final readonly class VarianceResult
{
    public function __construct(
        private float $actual,
        private float $baseline,
        private float $varianceAmount,
        private float $variancePercentage,
        private VarianceType $type,
    ) {}

    public function getActual(): float
    {
        return $this->actual;
    }

    public function getBaseline(): float
    {
        return $this->baseline;
    }

    public function getVarianceAmount(): float
    {
        return $this->varianceAmount;
    }

    public function getVariancePercentage(): float
    {
        return $this->variancePercentage;
    }

    public function getType(): VarianceType
    {
        return $this->type;
    }

    public function isFavorable(): bool
    {
        return $this->type === VarianceType::FAVORABLE;
    }

    public function isUnfavorable(): bool
    {
        return $this->type === VarianceType::UNFAVORABLE;
    }
}
