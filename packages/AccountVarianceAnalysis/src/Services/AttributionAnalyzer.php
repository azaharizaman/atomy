<?php

declare(strict_types=1);

namespace Nexus\AccountVarianceAnalysis\Services;

use Nexus\AccountVarianceAnalysis\Contracts\AttributionAnalyzerInterface;
use Nexus\AccountVarianceAnalysis\ValueObjects\VarianceAttribution;

/**
 * Pure logic for variance attribution analysis.
 */
final readonly class AttributionAnalyzer implements AttributionAnalyzerInterface
{
    public function attribute(float $totalVariance, array $factors): array
    {
        if ($totalVariance == 0) {
            return [];
        }

        $attributions = [];
        foreach ($factors as $factorName => $contribution) {
            $percentage = ($contribution / $totalVariance) * 100;
            $attributions[] = new VarianceAttribution(
                factorName: $factorName,
                contribution: $contribution,
                percentage: $percentage
            );
        }

        return $attributions;
    }

    public function decomposeVariance(
        float $actualPrice,
        float $budgetPrice,
        float $actualVolume,
        float $budgetVolume
    ): array {
        $priceVariance = ($actualPrice - $budgetPrice) * $budgetVolume;
        $volumeVariance = ($actualVolume - $budgetVolume) * $budgetPrice;
        $mixVariance = ($actualPrice - $budgetPrice) * ($actualVolume - $budgetVolume);

        return [
            'price' => $priceVariance,
            'volume' => $volumeVariance,
            'mix' => $mixVariance,
        ];
    }
}
