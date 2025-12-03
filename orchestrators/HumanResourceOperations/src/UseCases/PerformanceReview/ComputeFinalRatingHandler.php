<?php

declare(strict_types=1);

namespace Nexus\HumanResourceOperations\UseCases\PerformanceReview;

use Nexus\PerformanceReview\Contracts\PerformanceCalculatorInterface;

final readonly class ComputeFinalRatingHandler
{
    public function __construct(
        private PerformanceCalculatorInterface $calculator
    ) {}
    
    public function handle(string $appraisalId): array
    {
        // Compute final performance rating
        throw new \RuntimeException('Implementation pending');
    }
}
