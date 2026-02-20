<?php

declare(strict_types=1);

namespace Nexus\PerformanceReview\ValueObjects;

final readonly class AppraisalPeriod
{
    public function __construct(
        public \DateTimeImmutable $startDate,
        public \DateTimeImmutable $endDate,
    ) {
        if ($endDate < $startDate) {
            throw new \InvalidArgumentException('End date must be after start date');
        }
    }
}
