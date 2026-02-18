<?php

declare(strict_types=1);

namespace Nexus\SalesOperations\Services;

final readonly class MarginCalculator
{
    public function analyze(
        array $orderLines,
        string $costBasis = 'weighted_average',
        bool $includeLandedCost = false
    ): MarginAnalysis {
        $totalRevenue = 0.0;
        $totalCost = 0.0;

        foreach ($orderLines as $line) {
            $lineRevenue = $line['quantity'] * $line['unit_price'] * (1 - ($line['discount_percent'] ?? 0) / 100);
            $lineCost = $this->calculateLineCost($line, $costBasis, $includeLandedCost);

            $totalRevenue += $lineRevenue;
            $totalCost += $lineCost;
        }

        $grossProfit = $totalRevenue - $totalCost;
        $grossMarginPercent = $totalRevenue > 0 ? ($grossProfit / $totalRevenue) * 100 : 0.0;

        return new MarginAnalysis(
            totalRevenue: $totalRevenue,
            totalCost: $totalCost,
            grossProfit: $grossProfit,
            grossMarginPercent: $grossMarginPercent,
            netMarginPercent: $grossMarginPercent
        );
    }

    private function calculateLineCost(array $line, string $costBasis, bool $includeLandedCost): float
    {
        $baseCost = $line['unit_cost'] ?? $line['unit_price'] * 0.6;

        if ($includeLandedCost && isset($line['landed_cost_per_unit'])) {
            $baseCost += $line['landed_cost_per_unit'];
        }

        return $line['quantity'] * $baseCost;
    }
}

final readonly class MarginAnalysis
{
    public function __construct(
        public float $totalRevenue,
        public float $totalCost,
        public float $grossProfit,
        public float $grossMarginPercent,
        public float $netMarginPercent
    ) {}
}
