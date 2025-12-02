<?php

declare(strict_types=1);

namespace Nexus\FinancialRatios\Contracts;

use Nexus\FinancialRatios\ValueObjects\RatioResult;

/**
 * Contract for liquidity ratio calculations.
 */
interface LiquidityRatioInterface
{
    /**
     * Calculate current ratio (Current Assets / Current Liabilities).
     */
    public function currentRatio(float $currentAssets, float $currentLiabilities): RatioResult;

    /**
     * Calculate quick ratio ((Current Assets - Inventory) / Current Liabilities).
     */
    public function quickRatio(
        float $currentAssets,
        float $inventory,
        float $currentLiabilities
    ): RatioResult;

    /**
     * Calculate cash ratio (Cash / Current Liabilities).
     */
    public function cashRatio(float $cash, float $currentLiabilities): RatioResult;

    /**
     * Calculate working capital.
     */
    public function workingCapital(float $currentAssets, float $currentLiabilities): float;
}
