<?php

declare(strict_types=1);

namespace Nexus\SupplyChainOperations\Contracts;

interface AtpCalculationServiceInterface
{
    /**
     * Calculate comprehensive lead time data for ATP determination.
     *
     * @return array{
     *     baseDays: float,
     *     variance: float,
     *     varianceBuffer: float,
     *     reliabilityBuffer: float,
     *     seasonalBuffer: float,
     *     totalDays: int,
     *     vendorId: string,
     *     vendorAccuracy: float
     * }
     */
    public function calculateLeadTimeData(
        string $productId,
        ?string $preferredVendorId
    ): array;

    /**
     * Calculate confidence score based on various factors.
     */
    public function calculateConfidence(
        float $vendorAccuracy,
        float $leadTimeVariance,
        float $baseLeadTime
    ): float;
}
