<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DTOs\Vendor;

/**
 * Vendor rating data DTO.
 *
 * Captures vendor performance metrics and scoring.
 */
final readonly class VendorRatingData
{
    /**
     * @param string $vendorId Vendor being rated
     * @param float $overallScore Overall score (0-100)
     * @param float $qualityScore Quality score (0-100)
     * @param float $deliveryScore Delivery performance score (0-100)
     * @param float $priceScore Price competitiveness score (0-100)
     * @param float $serviceScore Service/responsiveness score (0-100)
     * @param float $complianceScore Compliance score (0-100)
     * @param string $ratingGrade Letter grade (A+, A, B+, B, C+, C, D, F)
     * @param int $totalOrders Total orders evaluated
     * @param int $defectCount Number of defective deliveries
     * @param int $lateDeliveries Number of late deliveries
     * @param float $defectRate Defect rate percentage
     * @param float $onTimeDeliveryRate On-time delivery percentage
     * @param \DateTimeImmutable $ratingPeriodStart Rating period start
     * @param \DateTimeImmutable $ratingPeriodEnd Rating period end
     * @param \DateTimeImmutable $calculatedAt When rating was calculated
     * @param array<string, mixed> $breakdowns Detailed score breakdowns
     */
    public function __construct(
        public string $vendorId,
        public float $overallScore,
        public float $qualityScore,
        public float $deliveryScore,
        public float $priceScore,
        public float $serviceScore,
        public float $complianceScore,
        public string $ratingGrade,
        public int $totalOrders,
        public int $defectCount,
        public int $lateDeliveries,
        public float $defectRate,
        public float $onTimeDeliveryRate,
        public \DateTimeImmutable $ratingPeriodStart,
        public \DateTimeImmutable $ratingPeriodEnd,
        public \DateTimeImmutable $calculatedAt,
        public array $breakdowns = [],
    ) {}

    /**
     * Create a new rating calculation.
     */
    public static function calculate(
        string $vendorId,
        float $qualityScore,
        float $deliveryScore,
        float $priceScore,
        float $serviceScore,
        float $complianceScore,
        int $totalOrders,
        int $defectCount,
        int $lateDeliveries,
        \DateTimeImmutable $periodStart,
        \DateTimeImmutable $periodEnd,
    ): self {
        // Weighted average for overall score
        // Quality: 30%, Delivery: 25%, Price: 20%, Service: 15%, Compliance: 10%
        $overallScore = (
            ($qualityScore * 0.30) +
            ($deliveryScore * 0.25) +
            ($priceScore * 0.20) +
            ($serviceScore * 0.15) +
            ($complianceScore * 0.10)
        );

        $defectRate = $totalOrders > 0 ? ($defectCount / $totalOrders) * 100 : 0;
        $onTimeRate = $totalOrders > 0 ? (($totalOrders - $lateDeliveries) / $totalOrders) * 100 : 100;

        $grade = self::calculateGrade($overallScore);

        return new self(
            vendorId: $vendorId,
            overallScore: round($overallScore, 2),
            qualityScore: $qualityScore,
            deliveryScore: $deliveryScore,
            priceScore: $priceScore,
            serviceScore: $serviceScore,
            complianceScore: $complianceScore,
            ratingGrade: $grade,
            totalOrders: $totalOrders,
            defectCount: $defectCount,
            lateDeliveries: $lateDeliveries,
            defectRate: round($defectRate, 2),
            onTimeDeliveryRate: round($onTimeRate, 2),
            ratingPeriodStart: $periodStart,
            ratingPeriodEnd: $periodEnd,
            calculatedAt: new \DateTimeImmutable(),
        );
    }

    /**
     * Create excellent vendor rating (for testing/seeding).
     */
    public static function excellent(string $vendorId): self
    {
        return self::calculate(
            vendorId: $vendorId,
            qualityScore: 95.0,
            deliveryScore: 98.0,
            priceScore: 90.0,
            serviceScore: 92.0,
            complianceScore: 100.0,
            totalOrders: 100,
            defectCount: 1,
            lateDeliveries: 2,
            periodStart: new \DateTimeImmutable('-1 year'),
            periodEnd: new \DateTimeImmutable(),
        );
    }

    /**
     * Create average vendor rating (for testing/seeding).
     */
    public static function average(string $vendorId): self
    {
        return self::calculate(
            vendorId: $vendorId,
            qualityScore: 75.0,
            deliveryScore: 70.0,
            priceScore: 80.0,
            serviceScore: 72.0,
            complianceScore: 85.0,
            totalOrders: 50,
            defectCount: 5,
            lateDeliveries: 8,
            periodStart: new \DateTimeImmutable('-1 year'),
            periodEnd: new \DateTimeImmutable(),
        );
    }

    /**
     * Create poor vendor rating (for testing/seeding).
     */
    public static function poor(string $vendorId): self
    {
        return self::calculate(
            vendorId: $vendorId,
            qualityScore: 55.0,
            deliveryScore: 50.0,
            priceScore: 60.0,
            serviceScore: 45.0,
            complianceScore: 70.0,
            totalOrders: 30,
            defectCount: 8,
            lateDeliveries: 12,
            periodStart: new \DateTimeImmutable('-1 year'),
            periodEnd: new \DateTimeImmutable(),
        );
    }

    private static function calculateGrade(float $score): string
    {
        return match (true) {
            $score >= 97 => 'A+',
            $score >= 93 => 'A',
            $score >= 90 => 'A-',
            $score >= 87 => 'B+',
            $score >= 83 => 'B',
            $score >= 80 => 'B-',
            $score >= 77 => 'C+',
            $score >= 73 => 'C',
            $score >= 70 => 'C-',
            $score >= 67 => 'D+',
            $score >= 60 => 'D',
            default => 'F',
        };
    }

    public function isPreferredVendor(): bool
    {
        return $this->overallScore >= 90.0 && in_array($this->ratingGrade, ['A+', 'A', 'A-'], true);
    }

    public function isAtRisk(): bool
    {
        return $this->overallScore < 70.0 || in_array($this->ratingGrade, ['D+', 'D', 'F'], true);
    }

    public function requiresReview(): bool
    {
        return $this->defectRate > 5.0 || $this->onTimeDeliveryRate < 90.0;
    }

    public function hasHighDefectRate(): bool
    {
        return $this->defectRate > 3.0;
    }

    public function hasDeliveryIssues(): bool
    {
        return $this->onTimeDeliveryRate < 95.0;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'vendor_id' => $this->vendorId,
            'overall_score' => $this->overallScore,
            'quality_score' => $this->qualityScore,
            'delivery_score' => $this->deliveryScore,
            'price_score' => $this->priceScore,
            'service_score' => $this->serviceScore,
            'compliance_score' => $this->complianceScore,
            'rating_grade' => $this->ratingGrade,
            'total_orders' => $this->totalOrders,
            'defect_count' => $this->defectCount,
            'late_deliveries' => $this->lateDeliveries,
            'defect_rate' => $this->defectRate,
            'on_time_delivery_rate' => $this->onTimeDeliveryRate,
            'rating_period_start' => $this->ratingPeriodStart->format('Y-m-d'),
            'rating_period_end' => $this->ratingPeriodEnd->format('Y-m-d'),
            'calculated_at' => $this->calculatedAt->format('Y-m-d H:i:s'),
            'is_preferred_vendor' => $this->isPreferredVendor(),
            'is_at_risk' => $this->isAtRisk(),
            'requires_review' => $this->requiresReview(),
            'breakdowns' => $this->breakdowns,
        ];
    }
}
