<?php

declare(strict_types=1);

namespace Nexus\PerformanceReview\Services;

use Nexus\PerformanceReview\Entities\Kpi;
use Nexus\PerformanceReview\ValueObjects\CompetencyScore;

final readonly class KpiEvaluator
{
    public function evaluate(Kpi $kpi, float $actualValue, float $targetValue): CompetencyScore
    {
        $score = ($actualValue / $targetValue) * 100;
        return new CompetencyScore(min(100, max(0, $score)));
    }
}
