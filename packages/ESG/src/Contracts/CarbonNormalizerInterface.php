<?php

declare(strict_types=1);

namespace Nexus\ESG\Contracts;

use Nexus\ESG\ValueObjects\EmissionsAmount;

/**
 * Interface for normalizing carbon emission data.
 */
interface CarbonNormalizerInterface
{
    /**
     * Normalize an emissions amount to a target unit (default tonnes).
     */
    public function normalize(EmissionsAmount $amount, string $targetUnit = 'tonnes'): EmissionsAmount;

    /**
     * Aggregate multiple emission amounts into a single total in tonnes.
     * 
     * @param array<EmissionsAmount> $amounts
     * @return EmissionsAmount
     */
    public function aggregate(array $amounts): EmissionsAmount;
}
