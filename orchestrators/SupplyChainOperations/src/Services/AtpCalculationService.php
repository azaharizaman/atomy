<?php

declare(strict_types=1);

namespace Nexus\SupplyChainOperations\Services;

use Nexus\Inventory\Contracts\InventoryAnalyticsRepositoryInterface;
use Nexus\ProcurementML\Contracts\DeliveryAnalyticsRepositoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Service for calculating Available-to-Promise lead times.
 *
 * Handles the heavy computation logic for ATP calculations including:
 * - Base lead time determination
 * - Variance buffer calculation
 * - Vendor reliability scoring
 * - Seasonal demand adjustments
 *
 * This service follows the Advanced Orchestrator Pattern where
 * calculations are separated from orchestration logic.
 */
final readonly class AtpCalculationService
{
    private const Z_SCORE_95 = 1.65;
    private const SEASONAL_BUFFER_DAYS = 3;

    public function __construct(
        private DeliveryAnalyticsRepositoryInterface $deliveryAnalytics,
        private InventoryAnalyticsRepositoryInterface $inventoryAnalytics,
        private ?LoggerInterface $logger = null
    ) {
    }

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
    ): array {
        $baseLeadTime = $this->inventoryAnalytics->getSupplierLeadTimeDays($productId);
        $leadTimeVariability = $this->inventoryAnalytics->getLeadTimeVariability($productId);

        $vendorId = $preferredVendorId;
        $vendorLeadTime = 0.0;
        $vendorVariance = 0.0;
        $vendorAccuracy = 1.0;

        if ($preferredVendorId !== null) {
            try {
                $vendorLeadTime = $this->deliveryAnalytics->getVendorAverageLeadTime($preferredVendorId);
                $vendorVariance = $this->deliveryAnalytics->getVendorLeadTimeVariance($preferredVendorId);
                $vendorAccuracy = $this->deliveryAnalytics->getVendorDeliveryAccuracy($preferredVendorId);
            } catch (\Throwable $e) {
                $this->logWarning("Failed to get vendor analytics for {$preferredVendorId}, using defaults");
            }
        }

        $effectiveLeadTime = ($vendorId !== null && $vendorLeadTime > 0)
            ? $vendorLeadTime
            : $baseLeadTime;

        $effectiveVariance = max($leadTimeVariability, $vendorVariance);
        $varianceBuffer = self::Z_SCORE_95 * $effectiveVariance;
        $reliabilityBuffer = (1.0 - $vendorAccuracy) * $effectiveLeadTime;
        $seasonalBuffer = $this->isSeasonalPeak() ? self::SEASONAL_BUFFER_DAYS : 0;

        $totalDays = (int) ceil(
            $effectiveLeadTime + $varianceBuffer + $reliabilityBuffer + $seasonalBuffer
        );

        return [
            'baseDays' => $effectiveLeadTime,
            'variance' => $effectiveVariance,
            'varianceBuffer' => $varianceBuffer,
            'reliabilityBuffer' => $reliabilityBuffer,
            'seasonalBuffer' => $seasonalBuffer,
            'totalDays' => max($totalDays, 1),
            'vendorId' => $vendorId ?? 'default',
            'vendorAccuracy' => $vendorAccuracy,
        ];
    }

    /**
     * Calculate confidence score based on various factors.
     */
    public function calculateConfidence(
        float $vendorAccuracy,
        float $leadTimeVariance,
        float $baseLeadTime
    ): float {
        $baseConfidence = $vendorAccuracy;
        $variancePenalty = min($leadTimeVariance / max($baseLeadTime, 1), 0.2);
        $seasonalPenalty = $this->isSeasonalPeak() ? 0.1 : 0.0;

        return max(0.0, min(1.0, $baseConfidence - $variancePenalty - $seasonalPenalty));
    }

    private function isSeasonalPeak(): bool
    {
        try {
            return $this->deliveryAnalytics->isSeasonalDemandPeak();
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function logWarning(string $message): void
    {
        if ($this->logger !== null) {
            $this->logger->warning($message);
        }
    }
}
