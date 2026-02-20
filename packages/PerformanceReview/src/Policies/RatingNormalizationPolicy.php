<?php

declare(strict_types=1);

namespace Nexus\PerformanceReview\Policies;

use Nexus\PerformanceReview\ValueObjects\Rating;

final readonly class RatingNormalizationPolicy
{
    public function normalize(array $ratings): array
    {
        return $ratings;
    }
}
