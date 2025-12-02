<?php

declare(strict_types=1);

namespace Nexus\AccountConsolidation\Services;

use Nexus\AccountConsolidation\Contracts\NciCalculatorInterface;

/**
 * Pure calculation logic for non-controlling interest.
 */
final readonly class NciCalculator implements NciCalculatorInterface
{
    public function calculate(
        string $subsidiaryId,
        float $ownershipPercentage,
        array $financialData
    ): array {
        $nciPercentage = 100.0 - $ownershipPercentage;
        $nciFactor = $nciPercentage / 100.0;

        $nciData = [
            'subsidiary_id' => $subsidiaryId,
            'nci_percentage' => $nciPercentage,
            'nci_equity' => 0.0,
            'nci_profit' => 0.0,
        ];

        if (isset($financialData['equity'])) {
            $nciData['nci_equity'] = (float) $financialData['equity'] * $nciFactor;
        }

        if (isset($financialData['net_income'])) {
            $nciData['nci_profit'] = $this->calculateNciShareOfProfit(
                $nciPercentage,
                (float) $financialData['net_income']
            );
        }

        return $nciData;
    }

    public function calculateNciShareOfProfit(float $nciPercentage, float $netIncome): float
    {
        return $netIncome * ($nciPercentage / 100.0);
    }
}
