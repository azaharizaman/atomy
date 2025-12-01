<?php

declare(strict_types=1);

namespace Nexus\AccountConsolidation\Services;

/**
 * Pure calculation logic for minority interest adjustments in step acquisitions.
 */
final readonly class MinorityInterestAdjuster
{
    /**
     * Calculate adjustment for step acquisition.
     *
     * @param float $previousOwnership Previous ownership percentage
     * @param float $newOwnership New ownership percentage
     * @param float $fairValueOfSubsidiary Fair value of subsidiary
     * @param float $previousCarryingAmount Previous carrying amount
     * @return array<string, float>
     */
    public function calculateStepAcquisitionAdjustment(
        float $previousOwnership,
        float $newOwnership,
        float $fairValueOfSubsidiary,
        float $previousCarryingAmount
    ): array {
        $previousFairValue = $fairValueOfSubsidiary * ($previousOwnership / 100.0);
        $gainOrLoss = $previousFairValue - $previousCarryingAmount;

        return [
            'gain_or_loss' => $gainOrLoss,
            'remeasured_value' => $previousFairValue,
            'ownership_increase' => $newOwnership - $previousOwnership,
        ];
    }

    /**
     * Calculate NCI adjustment for change in ownership without loss of control.
     *
     * @param float $ownershipChange Change in ownership percentage
     * @param float $subsidiaryEquity Total equity of subsidiary
     * @return float Adjustment to equity
     */
    public function calculateOwnershipChangeAdjustment(
        float $ownershipChange,
        float $subsidiaryEquity
    ): float {
        return $subsidiaryEquity * ($ownershipChange / 100.0);
    }

    /**
     * Reallocate NCI when additional shares are acquired.
     *
     * @param float $currentNci Current NCI balance
     * @param float $ownershipIncrease Percentage of ownership increase
     * @param float $totalNciPercentage Total NCI percentage before acquisition
     * @return float Amount to transfer from NCI to parent equity
     */
    public function reallocateNci(
        float $currentNci,
        float $ownershipIncrease,
        float $totalNciPercentage
    ): float {
        if ($totalNciPercentage <= 0) {
            return 0.0;
        }

        return $currentNci * ($ownershipIncrease / $totalNciPercentage);
    }
}
