<?php

declare(strict_types=1);

namespace Nexus\PerformanceReview\Contracts;

use Nexus\PerformanceReview\Entities\Appraisal;
use Nexus\PerformanceReview\ValueObjects\Rating;

interface PerformanceCalculatorInterface
{
    public function calculateOverallRating(Appraisal $appraisal): Rating;
    public function calculateWeightedScore(array $kpis): float;
}
