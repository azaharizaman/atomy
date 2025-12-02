<?php

declare(strict_types=1);

namespace Nexus\AccountVarianceAnalysis\Contracts;

use Nexus\AccountVarianceAnalysis\ValueObjects\VarianceAttribution;

/**
 * Contract for variance attribution analysis.
 */
interface AttributionAnalyzerInterface
{
    /**
     * Attribute variance to contributing factors.
     *
     * @param float $totalVariance
     * @param array<string, float> $factors Factor name to contribution
     * @return array<VarianceAttribution>
     */
    public function attribute(float $totalVariance, array $factors): array;

    /**
     * Decompose variance into price and volume components.
     *
     * @param float $actualPrice
     * @param float $budgetPrice
     * @param float $actualVolume
     * @param float $budgetVolume
     * @return array{price: float, volume: float, mix: float}
     */
    public function decomposeVariance(
        float $actualPrice,
        float $budgetPrice,
        float $actualVolume,
        float $budgetVolume
    ): array;
}
