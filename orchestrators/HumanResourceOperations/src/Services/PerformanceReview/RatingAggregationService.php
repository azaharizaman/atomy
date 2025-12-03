<?php

declare(strict_types=1);

namespace Nexus\HumanResourceOperations\Services\PerformanceReview;

use Nexus\PerformanceReview\Contracts\PerformanceCalculatorInterface;

final readonly class RatingAggregationService
{
    public function __construct(
        private PerformanceCalculatorInterface $calculator
    ) {}
    
    /**
     * Aggregate self-review and manager review into final rating
     */
    public function aggregateRatings(string $appraisalId): array
    {
        // Orchestrate rating aggregation
        // Apply weighting, calibration, and normalization
        throw new \RuntimeException('Implementation pending');
    }
}
