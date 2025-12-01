<?php

declare(strict_types=1);

namespace Nexus\FinancialRatios\Contracts;

use Nexus\FinancialRatios\ValueObjects\RatioResult;

/**
 * Base contract for ratio calculators.
 */
interface RatioCalculatorInterface
{
    /**
     * Calculate a ratio.
     *
     * @param float $numerator
     * @param float $denominator
     * @return RatioResult
     */
    public function calculate(float $numerator, float $denominator): RatioResult;

    /**
     * Get the ratio name.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Get the category of this ratio.
     *
     * @return \Nexus\FinancialRatios\Enums\RatioCategory
     */
    public function getCategory(): \Nexus\FinancialRatios\Enums\RatioCategory;
}
