<?php

declare(strict_types=1);

namespace Nexus\ESG\Services;

use Nexus\ESG\Contracts\CarbonNormalizerInterface;
use Nexus\ESG\ValueObjects\EmissionsAmount;

/**
 * Service for normalizing and aggregating carbon emission data.
 */
final readonly class CarbonNormalizer implements CarbonNormalizerInterface
{
    /**
     * @inheritDoc
     */
    public function normalize(EmissionsAmount $amount, string $targetUnit = 'tonnes'): EmissionsAmount
    {
        if ($amount->unit === $targetUnit) {
            return $amount;
        }

        return new EmissionsAmount($amount->toTonnes(), $targetUnit);
    }

    /**
     * @inheritDoc
     */
    public function aggregate(array $amounts): EmissionsAmount
    {
        $totalTonnes = 0.0;

        foreach ($amounts as $amount) {
            $totalTonnes += $amount->toTonnes();
        }

        return new EmissionsAmount($totalTonnes, 'tonnes');
    }
}
