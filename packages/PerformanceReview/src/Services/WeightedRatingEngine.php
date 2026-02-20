<?php

declare(strict_types=1);

namespace Nexus\PerformanceReview\Services;

final readonly class WeightedRatingEngine
{
    public function calculateWeighted(array $ratings, array $weights): float
    {
        $total = 0.0;
        foreach ($ratings as $i => $rating) {
            $total += $rating * ($weights[$i] ?? 1.0);
        }
        return $total;
    }
}
