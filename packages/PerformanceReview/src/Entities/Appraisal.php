<?php

declare(strict_types=1);

namespace Nexus\PerformanceReview\Entities;

use Nexus\PerformanceReview\ValueObjects\AppraisalPeriod;
use Nexus\PerformanceReview\ValueObjects\Rating;

final readonly class Appraisal
{
    public function __construct(
        public string $id,
        public string $employeeId,
        public string $reviewerId,
        public AppraisalPeriod $period,
        public ?Rating $rating = null,
        public string $status = 'draft',
        public ?\DateTimeImmutable $completedAt = null,
    ) {}
}
