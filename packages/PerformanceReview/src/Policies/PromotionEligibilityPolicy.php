<?php

declare(strict_types=1);

namespace Nexus\PerformanceReview\Policies;

use Nexus\PerformanceReview\ValueObjects\Rating;

final readonly class PromotionEligibilityPolicy
{
    public function isEligible(Rating $rating): bool
    {
        return $rating === Rating::OUTSTANDING || $rating === Rating::EXCEEDS;
    }
}
