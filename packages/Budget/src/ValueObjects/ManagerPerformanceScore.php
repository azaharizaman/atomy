<?php

declare(strict_types=1);

namespace Nexus\Budget\ValueObjects;

/**
 * Manager Performance Score value object
 * 
 * Immutable representation of budget manager performance metrics.
 */
final readonly class ManagerPerformanceScore
{
    public function __construct(
        private float $varianceScore,
        private float $forecastAccuracyScore,
        private float $investigationResponseScore,
        private float $overallScore,
        private string $performanceTier
    ) {}

    public function getVarianceScore(): float
    {
        return $this->varianceScore;
    }

    public function getForecastAccuracyScore(): float
    {
        return $this->forecastAccuracyScore;
    }

    public function getInvestigationResponseScore(): float
    {
        return $this->investigationResponseScore;
    }

    public function getOverallScore(): float
    {
        return $this->overallScore;
    }

    public function getPerformanceTier(): string
    {
        return $this->performanceTier;
    }

    /**
     * Calculate weighted overall score
     * 
     * @param float $varianceWeight Default 0.4
     * @param float $forecastWeight Default 0.3
     * @param float $investigationWeight Default 0.3
     */
    public static function calculateWeighted(
        float $varianceScore,
        float $forecastScore,
        float $investigationScore,
        float $varianceWeight = 0.4,
        float $forecastWeight = 0.3,
        float $investigationWeight = 0.3
    ): float {
        return ($varianceScore * $varianceWeight) +
               ($forecastScore * $forecastWeight) +
               ($investigationScore * $investigationWeight);
    }

    /**
     * Determine performance tier based on score
     */
    public static function determineTier(float $score): string
    {
        return match(true) {
            $score >= 90 => 'Gold',
            $score >= 75 => 'Silver',
            $score >= 60 => 'Bronze',
            default => 'Needs Improvement',
        };
    }

    /**
     * Get tier description
     */
    public function getTierDescription(): string
    {
        return match($this->performanceTier) {
            'Gold' => 'Exceptional budget management',
            'Silver' => 'Strong budget management',
            'Bronze' => 'Satisfactory budget management',
            default => 'Budget management needs improvement',
        };
    }
}
