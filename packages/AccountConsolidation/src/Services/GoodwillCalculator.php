<?php

declare(strict_types=1);

namespace Nexus\AccountConsolidation\Services;

/**
 * Pure calculation logic for goodwill in acquisition accounting.
 */
final readonly class GoodwillCalculator
{
    /**
     * Calculate goodwill on acquisition.
     *
     * @param float $purchaseConsideration Total consideration paid
     * @param float $fairValueOfNetAssets Fair value of identifiable net assets
     * @param float $nciValue Value of non-controlling interest
     * @return float Goodwill amount
     */
    public function calculate(
        float $purchaseConsideration,
        float $fairValueOfNetAssets,
        float $nciValue = 0.0
    ): float {
        return $purchaseConsideration + $nciValue - $fairValueOfNetAssets;
    }

    /**
     * Calculate bargain purchase gain (negative goodwill).
     *
     * @param float $purchaseConsideration
     * @param float $fairValueOfNetAssets
     * @param float $nciValue
     * @return float Bargain purchase gain (positive if gain exists)
     */
    public function calculateBargainPurchaseGain(
        float $purchaseConsideration,
        float $fairValueOfNetAssets,
        float $nciValue = 0.0
    ): float {
        $goodwill = $this->calculate($purchaseConsideration, $fairValueOfNetAssets, $nciValue);
        
        return $goodwill < 0 ? abs($goodwill) : 0.0;
    }

    /**
     * Test goodwill for impairment.
     *
     * @param float $carryingAmount Current carrying amount of goodwill
     * @param float $recoverableAmount Recoverable amount of cash-generating unit
     * @return float Impairment loss (0 if no impairment)
     */
    public function calculateImpairment(float $carryingAmount, float $recoverableAmount): float
    {
        return max(0.0, $carryingAmount - $recoverableAmount);
    }
}
