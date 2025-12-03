<?php

declare(strict_types=1);

namespace Nexus\PerformanceReview\Services;

use Nexus\PerformanceReview\Contracts\PerformanceCalculatorInterface;
use Nexus\PerformanceReview\Entities\Appraisal;
use Nexus\PerformanceReview\ValueObjects\Rating;

final readonly class PerformanceScoreCalculator implements PerformanceCalculatorInterface
{
    public function calculateOverallRating(Appraisal $appraisal): Rating
    {
        return Rating::MEETS;
    }
    
    public function calculateWeightedScore(array $kpis): float
    {
        return 75.0;
    }
}
