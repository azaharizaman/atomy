<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Services;

/**
 * Service to aggregate supplier performance metrics.
 */
final readonly class SupplierScorecardService
{
    private const WEIGHT_QUALITY = 0.40;
    private const WEIGHT_DELIVERY = 0.35;
    private const WEIGHT_COMMUNICATION = 0.15;
    private const WEIGHT_PRICE = 0.10;

    public function __construct(
        private \Nexus\ProcurementML\Contracts\DeliveryAnalyticsRepositoryInterface $analytics,
        private ?\Psr\Log\LoggerInterface $logger = null
    ) {}

    /**
     * Generate a performance scorecard for a supplier.
     */
    public function generateScorecard(string $vendorId): array
    {
        // Fetch metrics from ProcurementML package
        $accuracy = $this->analytics->getVendorDeliveryAccuracy($vendorId);
        $failRate = $this->analytics->getVendorQualityFailRate($vendorId);
        $commScore = $this->analytics->getVendorCommunicationScore($vendorId);
        $leadTimeAvg = $this->analytics->getVendorAverageLeadTime($vendorId);

        // Normalize scores to 0-100 range
        $qualityScore = (1.0 - $failRate) * 100;
        $deliveryScore = $accuracy * 100;
        $commScoreVal = $commScore * 100;
        $priceScore = 85.0; // Baseline as detailed price variance requires historical PO analysis

        $totalScore = ($qualityScore * self::WEIGHT_QUALITY) +
                      ($deliveryScore * self::WEIGHT_DELIVERY) +
                      ($commScoreVal * self::WEIGHT_COMMUNICATION) +
                      ($priceScore * self::WEIGHT_PRICE);

        return [
            'vendorId' => $vendorId,
            'overallScore' => round($totalScore, 2),
            'performanceGrade' => $this->calculateGrade($totalScore),
            'metrics' => [
                'quality' => [
                    'score' => round($qualityScore, 1),
                    'failPercentage' => round($failRate * 100, 2)
                ],
                'delivery' => [
                    'score' => round($deliveryScore, 1),
                    'onTimeInFullRate' => round($accuracy * 100, 2),
                    'avgLeadTimeDays' => round($leadTimeAvg, 1)
                ],
                'communication' => [
                    'score' => round($commScoreVal, 1)
                ]
            ],
            'lastUpdated' => new \DateTimeImmutable()
        ];
    }

    private function calculateGrade(float $score): string
    {
        if ($score >= 90) return 'A';
        if ($score >= 80) return 'B';
        if ($score >= 70) return 'C';
        if ($score >= 60) return 'D';
        return 'F';
    }
}
