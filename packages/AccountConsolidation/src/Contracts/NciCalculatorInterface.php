<?php

declare(strict_types=1);

namespace Nexus\AccountConsolidation\Contracts;

/**
 * Contract for non-controlling interest calculations.
 */
interface NciCalculatorInterface
{
    /**
     * Calculate NCI for a subsidiary.
     *
     * @param string $subsidiaryId
     * @param float $ownershipPercentage Parent's ownership percentage
     * @param array<string, mixed> $financialData Subsidiary's financial data
     * @return array<string, mixed>
     */
    public function calculate(
        string $subsidiaryId,
        float $ownershipPercentage,
        array $financialData
    ): array;

    /**
     * Calculate NCI share of profit or loss.
     *
     * @param float $nciPercentage
     * @param float $netIncome
     * @return float
     */
    public function calculateNciShareOfProfit(float $nciPercentage, float $netIncome): float;
}
