<?php

declare(strict_types=1);

namespace Nexus\AccountVarianceAnalysis\ValueObjects;

/**
 * Threshold configuration for significance evaluation.
 */
final readonly class SignificanceThreshold
{
    public function __construct(
        private float $amountThreshold,
        private float $percentageThreshold,
        private bool $requireBoth = false,
    ) {}

    public function getAmountThreshold(): float
    {
        return $this->amountThreshold;
    }

    public function getPercentageThreshold(): float
    {
        return $this->percentageThreshold;
    }

    public function requireBoth(): bool
    {
        return $this->requireBoth;
    }
}
